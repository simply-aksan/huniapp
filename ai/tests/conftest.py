import pytest
from fastapi.testclient import TestClient
from config import Settings, get_settings
from services.vector_store import get_vector_store
from main import app

TEST_SECRET = "test_phase11c_secret_key_valid_123"


def get_test_settings() -> Settings:
    return Settings(
        app_env="testing",
        huni_ai_secret=TEST_SECRET,
        max_sync_documents=10,
        max_title_length=50,
        max_content_length=500,
        max_question_length=200,
    )


@pytest.fixture(autouse=True)
def reset_state():
    """
    Reset vector store and override settings before each test.
    """
    store = get_vector_store()
    store.clear_all()
    app.dependency_overrides[get_settings] = get_test_settings
    yield
    store.clear_all()
    app.dependency_overrides.clear()


@pytest.fixture
def client() -> TestClient:
    return TestClient(app)


@pytest.fixture
def auth_headers() -> dict[str, str]:
    return {"X-HUNI-AI-SECRET": TEST_SECRET}
