import os
from functools import lru_cache
from pydantic import Field, field_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """
    Application configuration loaded from environment variables.
    """
    app_name: str = "HUNI AI Service"
    app_version: str = "0.1.0"
    app_env: str = Field(default="development", validation_alias="APP_ENV")
    
    # Shared secret header authentication
    huni_ai_secret: str = Field(default="", validation_alias="HUNI_AI_SECRET")
    
    # Payload limits
    max_sync_documents: int = 500
    max_title_length: int = 255
    max_content_length: int = 15000
    max_question_length: int = 1000

    # Persistent RAG Storage & Retrieval
    rag_db_path: str = Field(default="data/knowledge_base.db", validation_alias="HUNI_RAG_DB_PATH")
    rag_relevance_threshold: float = 0.20

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore"
    )

    @field_validator("huni_ai_secret")
    @classmethod
    def validate_secret_presence(cls, v: str) -> str:
        # Secret cannot be empty whitespace
        return v.strip()

    def is_secret_configured(self) -> bool:
        return bool(self.huni_ai_secret)

    def is_testing(self) -> bool:
        return self.app_env.lower() in ("test", "testing")


@lru_cache
def get_settings() -> Settings:
    """
    Cached settings provider.
    """
    return Settings()
