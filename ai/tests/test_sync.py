from fastapi.testclient import TestClient
from services.vector_store import get_vector_store


def test_sync_valid_payload_success(client: TestClient, auth_headers: dict[str, str]):
    payload = {
        "property_id": 1,
        "mode": "snapshot",
        "documents": [
            {
                "id": "1",
                "source_type": "rule",
                "title": "Jam Malam",
                "content": "Pintu ditutup jam 23.00 WIB.",
                "category": "jam_malam",
                "is_active": True,
                "version": 1,
            },
            {
                "id": "2",
                "source_type": "faq",
                "title": "Wi-Fi Kos",
                "content": "Password tersedia di papan pengumuman.",
                "category": "fasilitas",
                "is_active": True,
                "version": 1,
            },
        ],
    }

    response = client.post("/api/v1/rag/sync", headers=auth_headers, json=payload)
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "success"
    assert data["property_id"] == 1
    assert data["received"] == 2
    assert data["updated"] == 2
    assert data["skipped"] == 0
    assert data["total_active_for_property"] == 2

    store = get_vector_store()
    assert store.count(1) == 2


def test_sync_is_idempotent(client: TestClient, auth_headers: dict[str, str]):
    payload = {
        "property_id": 1,
        "mode": "snapshot",
        "documents": [
            {
                "id": 10,
                "source_type": "rule",
                "title": "Parkir Motor",
                "content": "Parkir di area basement.",
                "is_active": True,
                "version": 1,
            }
        ],
    }

    # First sync -> updated = 1, skipped = 0
    resp1 = client.post("/api/v1/rag/sync", headers=auth_headers, json=payload)
    assert resp1.status_code == 200
    assert resp1.json()["updated"] == 1
    assert resp1.json()["skipped"] == 0

    # Second sync with identical content and version -> updated = 0, skipped = 1
    resp2 = client.post("/api/v1/rag/sync", headers=auth_headers, json=payload)
    assert resp2.status_code == 200
    assert resp2.json()["updated"] == 0
    assert resp2.json()["skipped"] == 1
    assert resp2.json()["total_active_for_property"] == 1


def test_sync_snapshot_prunes_omitted_documents(client: TestClient, auth_headers: dict[str, str]):
    # Initial snapshot: docs A and B
    client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "mode": "snapshot",
            "documents": [
                {"id": 1, "source_type": "rule", "title": "A", "content": "Rule A", "is_active": True},
                {"id": 2, "source_type": "rule", "title": "B", "content": "Rule B", "is_active": True},
            ],
        },
    )
    store = get_vector_store()
    assert store.count(1) == 2

    # Second snapshot: only doc A is present (doc B was deleted in Laravel)
    resp = client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "mode": "snapshot",
            "documents": [
                {"id": 1, "source_type": "rule", "title": "A", "content": "Rule A", "is_active": True},
            ],
        },
    )
    assert resp.status_code == 200
    assert resp.json()["removed_or_deactivated"] == 1
    assert resp.json()["total_active_for_property"] == 1
    assert store.count(1) == 1


def test_sync_deactivates_inactive_documents(client: TestClient, auth_headers: dict[str, str]):
    resp = client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "mode": "snapshot",
            "documents": [
                {"id": 1, "source_type": "rule", "title": "Aktif", "content": "Konten", "is_active": True},
                {"id": 2, "source_type": "rule", "title": "Nonaktif", "content": "Konten", "is_active": False},
            ],
        },
    )
    assert resp.status_code == 200
    assert resp.json()["total_active_for_property"] == 1
    store = get_vector_store()
    assert store.count(1, active_only=True) == 1


def test_sync_enforces_property_isolation(client: TestClient, auth_headers: dict[str, str]):
    # Sync property 1
    client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "mode": "snapshot",
            "documents": [
                {"id": 1, "source_type": "rule", "title": "Rule Prop 1", "content": "Aturan Kos 1", "is_active": True},
            ],
        },
    )

    # Sync property 2
    client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 2,
            "mode": "snapshot",
            "documents": [
                {"id": 1, "source_type": "rule", "title": "Rule Prop 2", "content": "Aturan Kos 2", "is_active": True},
            ],
        },
    )

    store = get_vector_store()
    assert store.count(1) == 1
    assert store.count(2) == 1

    docs1 = store.get_documents(1)
    docs2 = store.get_documents(2)

    assert docs1[0].title == "Rule Prop 1"
    assert docs2[0].title == "Rule Prop 2"

    # Clearing or syncing property 1 MUST NOT affect property 2
    client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={"property_id": 1, "mode": "snapshot", "documents": []},
    )
    assert store.count(1) == 0
    assert store.count(2) == 1  # Property 2 is completely preserved!


def test_sync_rejects_invalid_source_type(client: TestClient, auth_headers: dict[str, str]):
    response = client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "documents": [
                {"id": 1, "source_type": "unsupported_type", "title": "A", "content": "B"},
            ],
        },
    )
    assert response.status_code == 422
    assert "source_type" in response.text


def test_sync_rejects_negative_or_zero_property_id(client: TestClient, auth_headers: dict[str, str]):
    response = client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={"property_id": 0, "documents": []},
    )
    assert response.status_code == 422


def test_sync_rejects_oversized_batch(client: TestClient, auth_headers: dict[str, str]):
    # conftest configures max_sync_documents = 10
    oversized_docs = [
        {"id": i, "source_type": "rule", "title": f"T{i}", "content": f"C{i}"}
        for i in range(15)
    ]
    response = client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={"property_id": 1, "documents": oversized_docs},
    )
    assert response.status_code == 422


def test_sync_incremental_mode(client: TestClient, auth_headers: dict[str, str]):
    # Seed doc 1 and doc 2
    client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "mode": "snapshot",
            "documents": [
                {"id": 1, "source_type": "rule", "title": "R1", "content": "C1"},
                {"id": 2, "source_type": "rule", "title": "R2", "content": "C2"},
            ],
        },
    )
    store = get_vector_store()
    assert store.count(1) == 2

    # Incremental update doc 3 without pruning doc 1 or doc 2
    resp = client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "mode": "incremental",
            "documents": [
                {"id": 3, "source_type": "faq", "title": "F3", "content": "C3"},
            ],
        },
    )
    assert resp.status_code == 200
    assert resp.json()["updated"] == 1
    assert store.count(1) == 3


def test_sync_rejects_whitespace_only_title_or_content(client: TestClient, auth_headers: dict[str, str]):
    resp = client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={
            "property_id": 1,
            "documents": [
                {"id": 1, "source_type": "rule", "title": "   ", "content": "Valid content"},
            ],
        },
    )
    assert resp.status_code == 422

