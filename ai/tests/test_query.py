from fastapi.testclient import TestClient


def test_query_on_empty_property_returns_index_empty(client: TestClient, auth_headers: dict[str, str]):
    response = client.post(
        "/api/v1/rag/query",
        headers=auth_headers,
        json={"property_id": 999, "question": "Kapan jam malam kos?"},
    )
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
    assert data["property_id"] == 999
    assert data["is_fallback"] is True
    assert data["retrieval_status"] == "index_empty"
    assert data["active_documents_count"] == 0
    assert "Belum ada aturan atau FAQ resmi" in data["answer"]
    assert data["sources"] == []


def test_query_on_populated_property_returns_grounded_retrieval_when_relevant(
    client: TestClient, auth_headers: dict[str, str]
):
    # Populate property 1
    client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "mode": "snapshot",
            "documents": [
                {
                    "id": 101,
                    "source_type": "rule",
                    "title": "Jam Malam dan Gerbang Utama",
                    "content": "Pintu gerbang utama kos ditutup setiap malam tepat pada pukul 23:00 WIB.",
                    "is_active": True,
                },
            ],
        },
    )

    response = client.post(
        "/api/v1/rag/query",
        headers=auth_headers,
        json={"property_id": 1, "question": "Jam berapa gerbang malam ditutup?"},
    )
    assert response.status_code == 200
    data = response.json()
    assert data["is_fallback"] is False
    assert data["retrieval_status"] == "grounded_retrieval"
    assert data["active_documents_count"] == 1
    assert len(data["sources"]) == 1
    source = data["sources"][0]
    assert source["id"] == "101"
    assert source["source_type"] == "rule"
    assert source["title"] == "Jam Malam dan Gerbang Utama"
    assert source["relevance_score"] >= 0.20
    assert "Jam Malam dan Gerbang Utama" in data["answer"]


def test_query_returns_insufficient_evidence_when_unrelated(
    client: TestClient, auth_headers: dict[str, str]
):
    client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "mode": "snapshot",
            "documents": [
                {
                    "id": 1,
                    "source_type": "rule",
                    "title": "Kebersihan Dapur",
                    "content": "Harap cuci piring setelah memasak.",
                    "is_active": True,
                },
            ],
        },
    )

    # Completely unrelated question
    response = client.post(
        "/api/v1/rag/query",
        headers=auth_headers,
        json={"property_id": 1, "question": "Bagaimana cara merakit roket luar angkasa?"},
    )
    assert response.status_code == 200
    data = response.json()
    assert data["is_fallback"] is True
    assert data["retrieval_status"] == "insufficient_evidence"
    assert data["sources"] == []
    assert "tidak dapat dijawab berdasarkan aturan atau FAQ resmi" in data["answer"]


def test_property_status_endpoint(client: TestClient, auth_headers: dict[str, str]):
    # Initially 0
    resp_empty = client.get("/api/v1/rag/properties/5/status", headers=auth_headers)
    assert resp_empty.status_code == 200
    assert resp_empty.json()["is_indexed"] is False
    assert resp_empty.json()["active_documents_count"] == 0

    # Sync a doc
    client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 5,
            "mode": "snapshot",
            "documents": [
                {"id": 1, "source_type": "faq", "title": "WiFi Kos", "content": "Password wifi adalah kosnyaman", "is_active": True},
            ],
        },
    )

    resp_synced = client.get("/api/v1/rag/properties/5/status", headers=auth_headers)
    assert resp_synced.status_code == 200
    assert resp_synced.json()["is_indexed"] is True
    assert resp_synced.json()["active_documents_count"] == 1


def test_query_never_exposes_other_properties_documents(client: TestClient, auth_headers: dict[str, str]):
    # Property 1 has secret wifi
    client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "mode": "snapshot",
            "documents": [
                {"id": 1, "source_type": "faq", "title": "Wi-Fi Rahasia", "content": "Password properti 1 adalah rahasia123", "is_active": True},
            ],
        },
    )

    # Querying Property 2 (which has 0 documents) must see count 0 and zero sources
    response = client.post(
        "/api/v1/rag/query",
        headers=auth_headers,
        json={"property_id": 2, "question": "Apa password wifi?"},
    )
    assert response.status_code == 200
    data = response.json()
    assert data["property_id"] == 2
    assert data["active_documents_count"] == 0
    assert data["sources"] == []
    assert data["retrieval_status"] == "index_empty"


def test_query_validation_errors(client: TestClient, auth_headers: dict[str, str]):
    # Empty question
    resp1 = client.post(
        "/api/v1/rag/query",
        headers=auth_headers,
        json={"property_id": 1, "question": "   "},
    )
    assert resp1.status_code == 422

    # Invalid property_id
    resp2 = client.post(
        "/api/v1/rag/query",
        headers=auth_headers,
        json={"property_id": -1, "question": "Halo"},
    )
    assert resp2.status_code == 422
