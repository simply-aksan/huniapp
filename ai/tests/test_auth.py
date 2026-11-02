from fastapi.testclient import TestClient
from main import app
from config import Settings, get_settings


def test_internal_endpoint_without_secret_returns_401(client: TestClient):
    response = client.post("/api/v1/rag/sync", json={"property_id": 1, "documents": []})
    assert response.status_code == 401
    assert "detail" in response.json()
    assert "secret" not in response.text.lower() or "missing required authentication" in response.text.lower()


def test_internal_endpoint_with_invalid_secret_returns_401(client: TestClient):
    response = client.post(
        "/api/v1/rag/sync",
        headers={"X-HUNI-AI-SECRET": "wrong_secret_key_123"},
        json={"property_id": 1, "documents": []},
    )
    assert response.status_code == 401
    assert response.json()["detail"] == "Invalid service secret"
    assert "wrong_secret_key_123" not in response.json()["detail"]


def test_internal_endpoint_with_valid_secret_succeeds(client: TestClient, auth_headers: dict[str, str]):
    response = client.post(
        "/api/v1/rag/sync",
        headers=auth_headers,
        json={"property_id": 1, "documents": []},
    )
    assert response.status_code == 200
    assert response.json()["status"] == "success"


def test_server_unconfigured_secret_fails_closed(client: TestClient):
    def get_unconfigured_settings() -> Settings:
        return Settings(app_env="testing", huni_ai_secret="")

    app.dependency_overrides[get_settings] = get_unconfigured_settings

    response = client.post(
        "/api/v1/rag/sync",
        headers={"X-HUNI-AI-SECRET": "any_secret"},
        json={"property_id": 1, "documents": []},
    )
    assert response.status_code == 401
    assert "unconfigured" in response.json()["detail"]
