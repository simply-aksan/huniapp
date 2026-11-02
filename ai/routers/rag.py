from typing import Literal
from fastapi import APIRouter, Depends, HTTPException, status
from pydantic import BaseModel, Field, field_validator
from services.authentication import verify_service_secret
from services.vector_store import BaseVectorStore, StoredDocument, get_vector_store
from config import Settings, get_settings

router = APIRouter(
    prefix="/api/v1/rag",
    tags=["Knowledge Base RAG"],
    dependencies=[Depends(verify_service_secret)],
)


class KnowledgeDocumentIn(BaseModel):
    """
    Schema for a single knowledge document sent by Laravel.
    """
    id: str | int = Field(..., description="Stable Laravel primary key")
    source_type: Literal["rule", "faq"] = Field(..., description="Source entity type")
    title: str = Field(..., min_length=1, max_length=255, description="Document title or summary")
    content: str = Field(..., min_length=1, max_length=15000, description="Document text content")
    category: str | None = Field(default=None, max_length=100, description="Optional category")
    is_active: bool = Field(default=True, description="Whether the document is currently active")
    version: str | int = Field(default=1, description="Revision or timestamp marker")

    @field_validator("title", "content")
    @classmethod
    def strip_whitespace(cls, v: str) -> str:
        cleaned = v.strip()
        if not cleaned:
            raise ValueError("Field must not be empty or whitespace only")
        return cleaned


class KnowledgeSyncRequest(BaseModel):
    """
    Payload for property knowledge base synchronization.
    """
    property_id: int = Field(..., gt=0, description="Target property ID in Laravel")
    mode: Literal["snapshot", "incremental"] = Field(
        default="snapshot",
        description="Sync mode: 'snapshot' prunes missing records; 'incremental' only upserts provided docs."
    )
    documents: list[KnowledgeDocumentIn] = Field(
        ...,
        max_length=500,
        description="List of knowledge documents for the given property"
    )


class KnowledgeSyncResponse(BaseModel):
    status: str = "success"
    property_id: int
    mode: str
    received: int
    updated: int
    removed_or_deactivated: int
    skipped: int
    total_active_for_property: int
    message: str


class PropertyIndexStatusResponse(BaseModel):
    property_id: int
    is_indexed: bool
    active_documents_count: int
    last_indexed_at: str | None


class RagSourceItem(BaseModel):
    id: str
    source_type: Literal["rule", "faq"]
    title: str
    content: str
    category: str | None = None
    relevance_score: float = Field(..., description="Cosine similarity score (0.0 to 1.0), not a probability")


class RagQueryRequest(BaseModel):
    """
    Query request for knowledge retrieval within a property boundary.
    """
    property_id: int = Field(..., gt=0, description="Property ID to scope retrieval")
    question: str = Field(..., min_length=1, max_length=1000, description="User inquiry")
    top_k: int = Field(default=3, ge=1, le=10, description="Maximum citations to retrieve")

    @field_validator("question")
    @classmethod
    def validate_question(cls, v: str) -> str:
        cleaned = v.strip()
        if not cleaned:
            raise ValueError("Question cannot be empty or whitespace only")
        return cleaned


class RagQueryResponse(BaseModel):
    status: str = "ok"
    property_id: int
    question: str
    retrieval_status: Literal["grounded_retrieval", "insufficient_evidence", "index_empty"]
    is_fallback: bool
    answer: str
    sources: list[RagSourceItem]
    active_documents_count: int
    message: str


@router.get("/properties/{property_id}/status", response_model=PropertyIndexStatusResponse)
async def get_property_rag_status(
    property_id: int,
    vector_store: BaseVectorStore = Depends(get_vector_store),
) -> PropertyIndexStatusResponse:
    """
    Get current knowledge base indexing readiness and statistics for a given property.
    Allows Laravel to verify whether persistent index is populated.
    """
    status_info = vector_store.get_property_status(property_id)
    return PropertyIndexStatusResponse(
        property_id=status_info["property_id"],
        is_indexed=status_info["is_indexed"],
        active_documents_count=status_info["active_documents_count"],
        last_indexed_at=status_info["last_indexed_at"],
    )


@router.post("/sync", response_model=KnowledgeSyncResponse)
async def sync_knowledge(
    payload: KnowledgeSyncRequest,
    vector_store: BaseVectorStore = Depends(get_vector_store),
    settings: Settings = Depends(get_settings),
) -> KnowledgeSyncResponse:
    """
    Synchronize knowledge base documents (Rules and FAQs) for a given property.
    Enforces payload limits, idempotency, and strict property isolation.
    """
    if len(payload.documents) > settings.max_sync_documents:
        status_code = getattr(status, "HTTP_422_UNPROCESSABLE_CONTENT", 422)
        raise HTTPException(
            status_code=status_code,
            detail=f"Document count exceeds maximum batch limit of {settings.max_sync_documents}",
        )

    # Convert Pydantic models to domain StoredDocument objects
    stored_docs = [
        StoredDocument(
            source_id=str(doc.id),
            source_type=doc.source_type,
            property_id=payload.property_id,
            title=doc.title,
            content=doc.content,
            category=doc.category,
            is_active=doc.is_active,
            version=doc.version,
        )
        for doc in payload.documents
    ]

    if payload.mode == "snapshot":
        summary = vector_store.sync_snapshot(payload.property_id, stored_docs)
    else:
        # Incremental mode
        updated = 0
        skipped = 0
        removed = 0
        for doc in stored_docs:
            if not doc.is_active:
                if vector_store.delete_document(payload.property_id, doc.source_type, doc.source_id):
                    removed += 1
                else:
                    skipped += 1
            else:
                if vector_store.upsert_document(payload.property_id, doc):
                    updated += 1
                else:
                    skipped += 1

        summary = {
            "received": len(stored_docs),
            "updated": updated,
            "removed_or_deactivated": removed,
            "skipped": skipped,
            "total_active_for_property": vector_store.count(payload.property_id, active_only=True),
        }

    return KnowledgeSyncResponse(
        status="success",
        property_id=payload.property_id,
        mode=payload.mode,
        received=summary["received"],
        updated=summary["updated"],
        removed_or_deactivated=summary["removed_or_deactivated"],
        skipped=summary["skipped"],
        total_active_for_property=summary["total_active_for_property"],
        message="Knowledge synchronization completed successfully",
    )


@router.post("/query", response_model=RagQueryResponse)
async def query_knowledge(
    payload: RagQueryRequest,
    vector_store: BaseVectorStore = Depends(get_vector_store),
    settings: Settings = Depends(get_settings),
) -> RagQueryResponse:
    """
    Query knowledge base within a strict property scope.
    Performs persistent TF-IDF & bi-gram semantic/lexical similarity retrieval.
    Returns honest, grounded citations or safe fallback without hallucination.
    """
    property_status = vector_store.get_property_status(payload.property_id)
    doc_count = property_status["active_documents_count"]

    if doc_count == 0:
        return RagQueryResponse(
            status="ok",
            property_id=payload.property_id,
            question=payload.question,
            retrieval_status="index_empty",
            is_fallback=True,
            answer=(
                "Belum ada aturan atau FAQ resmi yang tercatat untuk kos ini. "
                "Silakan tanyakan langsung kepada pemilik kos untuk informasi lebih lanjut."
            ),
            sources=[],
            active_documents_count=0,
            message="No active knowledge documents found for this property",
        )

    # Perform retrieval with configured threshold
    retrieved_items = vector_store.retrieve_relevant_documents(
        property_id=payload.property_id,
        query=payload.question,
        top_k=payload.top_k,
        threshold=settings.rag_relevance_threshold,
    )

    if not retrieved_items:
        return RagQueryResponse(
            status="ok",
            property_id=payload.property_id,
            question=payload.question,
            retrieval_status="insufficient_evidence",
            is_fallback=True,
            answer=(
                "Pertanyaan Anda tidak dapat dijawab berdasarkan aturan atau FAQ resmi kos yang tersedia. "
                "Untuk kepastian atau hal khusus, silakan hubungi langsung pemilik kos."
            ),
            sources=[],
            active_documents_count=doc_count,
            message="No documents exceeded relevance threshold",
        )

    # Format structured answer based on retrieved official documents
    sources = []
    answer_sections = ["Berikut informasi resmi yang relevan dari panduan kos:"]
    for idx, item in enumerate(retrieved_items, start=1):
        doc: StoredDocument = item["document"]
        score: float = item["score"]
        sources.append(
            RagSourceItem(
                id=doc.source_id,
                source_type=doc.source_type,
                title=doc.title,
                content=doc.content,
                category=doc.category,
                relevance_score=score,
            )
        )
        tag = "Aturan" if doc.source_type == "rule" else "FAQ"
        answer_sections.append(f"{idx}. [{tag}] {doc.title}\n{doc.content}")

    structured_answer = "\n\n".join(answer_sections)

    return RagQueryResponse(
        status="ok",
        property_id=payload.property_id,
        question=payload.question,
        retrieval_status="grounded_retrieval",
        is_fallback=False,
        answer=structured_answer,
        sources=sources,
        active_documents_count=doc_count,
        message=f"Retrieved {len(sources)} relevant document(s)",
    )

