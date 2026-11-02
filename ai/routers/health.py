from fastapi import APIRouter, Depends
from pydantic import BaseModel
from config import Settings, get_settings

router = APIRouter(tags=["Health"])


class HealthResponse(BaseModel):
    status: str = "healthy"
    app: str
    version: str


@router.get("/health", response_model=HealthResponse)
async def get_health(settings: Settings = Depends(get_settings)) -> HealthResponse:
    """
    Public healthcheck endpoint.
    Exposes minimal operational status without leaking environment details,
    host configuration, or authentication credentials.
    """
    return HealthResponse(
        status="healthy",
        app=settings.app_name,
        version=settings.app_version,
    )
