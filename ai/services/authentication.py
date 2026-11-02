import secrets
from fastapi import Header, HTTPException, status, Depends
from config import Settings, get_settings

HEADER_AI_SECRET = "X-HUNI-AI-SECRET"


async def verify_service_secret(
    x_huni_ai_secret: str | None = Header(default=None, alias=HEADER_AI_SECRET),
    settings: Settings = Depends(get_settings),
) -> None:
    """
    Validate that the incoming request contains a valid service secret in the X-HUNI-AI-SECRET header.
    Constant-time comparison is used to mitigate timing attacks.
    Secret value is never leaked in log files or exception messages.
    """
    if not settings.is_secret_configured():
        # Fail closed if server has no secret configured
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Service authentication is unconfigured on server",
            headers={"WWW-Authenticate": HEADER_AI_SECRET},
        )

    if not x_huni_ai_secret:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Missing required authentication header",
            headers={"WWW-Authenticate": HEADER_AI_SECRET},
        )

    # Constant-time comparison
    is_valid = secrets.compare_digest(
        x_huni_ai_secret.encode("utf-8"),
        settings.huni_ai_secret.encode("utf-8")
    )

    if not is_valid:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid service secret",
            headers={"WWW-Authenticate": HEADER_AI_SECRET},
        )
