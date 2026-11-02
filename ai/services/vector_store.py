import os
import sqlite3
from abc import ABC, abstractmethod
from dataclasses import dataclass, field
from datetime import datetime, timezone
from typing import Literal

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.pipeline import FeatureUnion

from contextlib import contextmanager
from config import get_settings

INDONESIAN_DOMAIN_SYNONYMS: dict[str, list[str]] = {
    "tidur": ["menginap"],
    "bermalam": ["menginap"],
    "nginap": ["menginap"],
    "sahabat": ["teman", "tamu"],
    "kawan": ["teman", "tamu"],
    "ongkos": ["biaya", "sewa"],
    "tarif": ["biaya"],
    "karcis": ["parkir", "biaya"],
    "gembok": ["kunci", "gerbang"],
    "pagar": ["gerbang", "pintu"],
    "kulkas": ["elektronik", "listrik"],
    "dispenser": ["elektronik", "listrik"],
    "rek": ["rekening"],
    "tf": ["transfer"],
}


def expand_query_with_synonyms(query: str) -> str:
    """
    Lightweight, deterministic domain synonym expansion for Indonesian kos terminology.
    Improves paraphrase retrieval without altering threshold or hallucinating facts.
    """
    tokens = query.lower().split()
    expanded_tokens = list(tokens)
    for token in tokens:
        clean_token = token.strip("?,.!;:")
        if clean_token in INDONESIAN_DOMAIN_SYNONYMS:
            expanded_tokens.extend(INDONESIAN_DOMAIN_SYNONYMS[clean_token])
    return " ".join(expanded_tokens)


@dataclass



class StoredDocument:
    """
    Representation of a Knowledge Base document indexed in the store.
    """
    source_id: str
    source_type: Literal["rule", "faq"]
    property_id: int
    title: str
    content: str
    category: str | None = None
    is_active: bool = True
    version: str | int = 1
    indexed_at: datetime = field(default_factory=lambda: datetime.now(timezone.utc))

    @property
    def composite_key(self) -> str:
        return f"{self.source_type}:{self.source_id}"

    def is_identical(self, other: "StoredDocument") -> bool:
        return (
            self.composite_key == other.composite_key
            and str(self.version) == str(other.version)
            and self.is_active == other.is_active
            and self.title == other.title
            and self.content == other.content
            and self.category == other.category
        )


class BaseVectorStore(ABC):
    """
    Abstract contract for persistent knowledge base & retrieval store.
    """
    @abstractmethod
    def upsert_document(self, property_id: int, doc: StoredDocument) -> bool:
        pass

    @abstractmethod
    def sync_snapshot(
        self, property_id: int, incoming_docs: list[StoredDocument]
    ) -> dict[str, int]:
        pass

    @abstractmethod
    def delete_document(self, property_id: int, source_type: str, source_id: str) -> bool:
        pass

    @abstractmethod
    def get_documents(self, property_id: int, active_only: bool = True) -> list[StoredDocument]:
        pass

    @abstractmethod
    def count(self, property_id: int, active_only: bool = True) -> int:
        pass

    @abstractmethod
    def clear_property(self, property_id: int) -> int:
        pass

    @abstractmethod
    def clear_all(self) -> None:
        pass

    @abstractmethod
    def retrieve_relevant_documents(
        self, property_id: int, query: str, top_k: int = 3, threshold: float = 0.20
    ) -> list[dict]:
        """
        Perform retrieval across active documents for property_id.
        Returns list of {'document': StoredDocument, 'score': float}
        """
        pass

    @abstractmethod
    def get_property_status(self, property_id: int) -> dict:
        """
        Get index readiness and document statistics for a property.
        """
        pass


class PersistentSqliteVectorStore(BaseVectorStore):
    """
    Persistent SQLite-backed knowledge base and vector/lexical retrieval store.
    Guarantees persistence across process restarts without external vector DB dependencies.
    """
    def __init__(self, db_path: str | None = None) -> None:
        if db_path is None:
            settings = get_settings()
            db_path = settings.rag_db_path

        if db_path != ":memory:" and not os.path.isabs(db_path):
            base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
            db_path = os.path.normpath(os.path.join(base_dir, db_path))

        self.db_path = db_path
        if self.db_path != ":memory:":
            os.makedirs(os.path.dirname(os.path.abspath(self.db_path)), exist_ok=True)

        self._init_db()

    @contextmanager
    def _get_connection(self):
        conn = sqlite3.connect(self.db_path, timeout=30.0)
        conn.row_factory = sqlite3.Row
        try:
            with conn:
                yield conn
        finally:
            conn.close()

    def _init_db(self) -> None:
        with self._get_connection() as conn:
            if self.db_path != ":memory:":
                conn.execute("PRAGMA journal_mode=WAL;")
                conn.execute("PRAGMA synchronous=NORMAL;")
            conn.execute("""
                CREATE TABLE IF NOT EXISTS stored_documents (
                    property_id INTEGER NOT NULL,
                    source_type TEXT NOT NULL,
                    source_id TEXT NOT NULL,
                    title TEXT NOT NULL,
                    content TEXT NOT NULL,
                    category TEXT,
                    is_active INTEGER NOT NULL,
                    version TEXT NOT NULL,
                    indexed_at TEXT NOT NULL,
                    PRIMARY KEY (property_id, source_type, source_id)
                )
            """)
            conn.execute("""
                CREATE INDEX IF NOT EXISTS idx_docs_property_active 
                ON stored_documents(property_id, is_active)
            """)


    def upsert_document(self, property_id: int, doc: StoredDocument) -> bool:
        with self._get_connection() as conn:
            existing = conn.execute(
                """
                SELECT version, is_active, title, content, category 
                FROM stored_documents 
                WHERE property_id = ? AND source_type = ? AND source_id = ?
                """,
                (property_id, doc.source_type, doc.source_id),
            ).fetchone()

            if existing:
                is_same = (
                    str(existing["version"]) == str(doc.version)
                    and bool(existing["is_active"]) == doc.is_active
                    and existing["title"] == doc.title
                    and existing["content"] == doc.content
                    and existing["category"] == doc.category
                )
                if is_same:
                    return False

            indexed_at_iso = doc.indexed_at.isoformat()
            conn.execute(
                """
                INSERT INTO stored_documents 
                (property_id, source_type, source_id, title, content, category, is_active, version, indexed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(property_id, source_type, source_id) DO UPDATE SET
                    title = excluded.title,
                    content = excluded.content,
                    category = excluded.category,
                    is_active = excluded.is_active,
                    version = excluded.version,
                    indexed_at = excluded.indexed_at
                """,
                (
                    property_id,
                    doc.source_type,
                    doc.source_id,
                    doc.title,
                    doc.content,
                    doc.category,
                    1 if doc.is_active else 0,
                    str(doc.version),
                    indexed_at_iso,
                ),
            )
            conn.commit()
            return True

    def sync_snapshot(
        self, property_id: int, incoming_docs: list[StoredDocument]
    ) -> dict[str, int]:
        with self._get_connection() as conn:
            # 1. Fetch current keys for this property
            cursor = conn.execute(
                "SELECT source_type, source_id, version, is_active, title, content, category FROM stored_documents WHERE property_id = ?",
                (property_id,),
            )
            existing_rows = {f"{row['source_type']}:{row['source_id']}": row for row in cursor.fetchall()}

            incoming_keys = {doc.composite_key for doc in incoming_docs}
            received = len(incoming_docs)
            updated = 0
            skipped = 0
            removed_or_deactivated = 0

            # 2. Process incoming
            for doc in incoming_docs:
                key = doc.composite_key
                indexed_at_iso = doc.indexed_at.isoformat()

                if not doc.is_active:
                    if key in existing_rows:
                        conn.execute(
                            "DELETE FROM stored_documents WHERE property_id = ? AND source_type = ? AND source_id = ?",
                            (property_id, doc.source_type, doc.source_id),
                        )
                        removed_or_deactivated += 1
                    else:
                        skipped += 1
                    continue

                if key in existing_rows:
                    row = existing_rows[key]
                    is_identical = (
                        str(row["version"]) == str(doc.version)
                        and bool(row["is_active"]) == doc.is_active
                        and row["title"] == doc.title
                        and row["content"] == doc.content
                        and row["category"] == doc.category
                    )
                    if is_identical:
                        skipped += 1
                        continue

                conn.execute(
                    """
                    INSERT INTO stored_documents 
                    (property_id, source_type, source_id, title, content, category, is_active, version, indexed_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON CONFLICT(property_id, source_type, source_id) DO UPDATE SET
                        title = excluded.title,
                        content = excluded.content,
                        category = excluded.category,
                        is_active = excluded.is_active,
                        version = excluded.version,
                        indexed_at = excluded.indexed_at
                    """,
                    (
                        property_id,
                        doc.source_type,
                        doc.source_id,
                        doc.title,
                        doc.content,
                        doc.category,
                        1 if doc.is_active else 0,
                        str(doc.version),
                        indexed_at_iso,
                    ),
                )
                updated += 1

            # 3. Prune obsolete documents
            for old_key, old_row in existing_rows.items():
                if old_key not in incoming_keys:
                    conn.execute(
                        "DELETE FROM stored_documents WHERE property_id = ? AND source_type = ? AND source_id = ?",
                        (property_id, old_row["source_type"], old_row["source_id"]),
                    )
                    removed_or_deactivated += 1

            conn.commit()

            total_active = conn.execute(
                "SELECT COUNT(*) FROM stored_documents WHERE property_id = ? AND is_active = 1",
                (property_id,),
            ).fetchone()[0]

            return {
                "received": received,
                "updated": updated,
                "removed_or_deactivated": removed_or_deactivated,
                "skipped": skipped,
                "total_active_for_property": total_active,
            }

    def delete_document(self, property_id: int, source_type: str, source_id: str) -> bool:
        with self._get_connection() as conn:
            cursor = conn.execute(
                "DELETE FROM stored_documents WHERE property_id = ? AND source_type = ? AND source_id = ?",
                (property_id, source_type, source_id),
            )
            conn.commit()
            return cursor.rowcount > 0

    def get_documents(self, property_id: int, active_only: bool = True) -> list[StoredDocument]:
        with self._get_connection() as conn:
            query = "SELECT * FROM stored_documents WHERE property_id = ?"
            params: list = [property_id]
            if active_only:
                query += " AND is_active = 1"
            query += " ORDER BY source_type, source_id"

            rows = conn.execute(query, params).fetchall()
            return [
                StoredDocument(
                    source_id=str(r["source_id"]),
                    source_type=r["source_type"],
                    property_id=r["property_id"],
                    title=r["title"],
                    content=r["content"],
                    category=r["category"],
                    is_active=bool(r["is_active"]),
                    version=r["version"],
                    indexed_at=datetime.fromisoformat(r["indexed_at"]),
                )
                for r in rows
            ]

    def count(self, property_id: int, active_only: bool = True) -> int:
        with self._get_connection() as conn:
            query = "SELECT COUNT(*) FROM stored_documents WHERE property_id = ?"
            params: list = [property_id]
            if active_only:
                query += " AND is_active = 1"
            return conn.execute(query, params).fetchone()[0]

    def clear_property(self, property_id: int) -> int:
        with self._get_connection() as conn:
            cursor = conn.execute(
                "DELETE FROM stored_documents WHERE property_id = ?", (property_id,)
            )
            conn.commit()
            return cursor.rowcount

    def clear_all(self) -> None:
        with self._get_connection() as conn:
            conn.execute("DELETE FROM stored_documents")
            conn.commit()

    def get_property_status(self, property_id: int) -> dict:
        with self._get_connection() as conn:
            row = conn.execute(
                """
                SELECT COUNT(*) as total_active, MAX(indexed_at) as last_indexed
                FROM stored_documents 
                WHERE property_id = ? AND is_active = 1
                """,
                (property_id,),
            ).fetchone()

            total_active = row["total_active"] if row else 0
            last_indexed = row["last_indexed"] if row and row["last_indexed"] else None

            return {
                "property_id": property_id,
                "is_indexed": total_active > 0,
                "active_documents_count": total_active,
                "last_indexed_at": last_indexed,
            }

    def retrieve_relevant_documents(
        self, property_id: int, query: str, top_k: int = 3, threshold: float = 0.20
    ) -> list[dict]:
        """
        Multilingual-compatible semantic & lexical retrieval using hybrid Word + Char n-gram TF-IDF and Cosine Similarity.
        Strictly limits document pool to active documents belonging to property_id.
        """
        cleaned_query = query.strip()
        if not cleaned_query:
            return []

        active_docs = self.get_documents(property_id, active_only=True)
        if not active_docs:
            return []

        # Prepare corpus from category, title, and content
        corpus = [
            f"{doc.category or ''} {doc.title}. {doc.content}".strip()
            for doc in active_docs
        ]

        # Hybrid word + character n-grams with word priority (1.0 vs 0.25)
        # Allows handling light typos and paraphrases while firmly suppressing unrelated inquiries.
        union = FeatureUnion([
            (
                "word",
                TfidfVectorizer(
                    ngram_range=(1, 2),
                    sublinear_tf=True,
                    strip_accents="unicode",
                    lowercase=True,
                ),
            ),
            (
                "char",
                TfidfVectorizer(
                    analyzer="char_wb",
                    ngram_range=(3, 4),
                    sublinear_tf=True,
                    strip_accents="unicode",
                    lowercase=True,
                ),
            ),
        ], transformer_weights={"word": 1.0, "char": 0.25})

        try:
            tfidf_matrix = union.fit_transform(corpus)
            expanded_query = expand_query_with_synonyms(cleaned_query)
            query_vec = union.transform([expanded_query])
            similarities = cosine_similarity(query_vec, tfidf_matrix).flatten()
        except ValueError:
            # Vocabulary empty or unparseable query
            return []

        scored_results = []
        for doc, score in zip(active_docs, similarities):
            float_score = round(float(score), 4)
            if float_score >= threshold:
                scored_results.append({
                    "document": doc,
                    "score": float_score,
                })

        # Sort descending by relevance score
        scored_results.sort(key=lambda x: x["score"], reverse=True)
        return scored_results[:top_k]



# Global singleton instance
_persistent_store: PersistentSqliteVectorStore | None = None


def get_vector_store() -> BaseVectorStore:
    """Dependency provider for persistent vector store."""
    global _persistent_store
    if _persistent_store is None:
        _persistent_store = PersistentSqliteVectorStore()
    return _persistent_store


def set_vector_store(store: BaseVectorStore) -> None:
    """Setter for test fixtures."""
    global _persistent_store
    _persistent_store = store
