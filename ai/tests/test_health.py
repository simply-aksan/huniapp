from fastapi.testclient import TestClient


def test_healthcheck_returns_healthy_status_without_auth(client: TestClient):
    response = client.get("/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "healthy"
    assert "app" in data
    assert "version" in data
    
    # Verify no internal details or secrets are leaked
    assert "secret" not in data
    assert "huni_ai_secret" not in data
    assert "env" not in data
