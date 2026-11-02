import logging
import sys
from contextlib import asynccontextmanager
from typing import AsyncGenerator

from fastapi import FastAPI, Request, status
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse

from config import get_settings
from routers import health, rag, risk

# Configure secure logging that never dumps sensitive authorization headers or secrets
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)],
)
logger = logging.getLogger("huni.ai")


@asynccontextmanager
async def lifespan(app: FastAPI) -> AsyncGenerator[None, None]:
    """
    Application startup and shutdown lifecycle manager.
    Verifies secure configuration on startup.
    """
    settings = get_settings()
    logger.info("Starting %s v%s [Env: %s]", settings.app_name, settings.app_version, settings.app_env)

    # In non-testing environments, ensure authentication secret is configured
    if not settings.is_testing() and not settings.is_secret_configured():
        logger.error("FATAL: HUNI_AI_SECRET is not configured! Shutting down for security.")
        raise RuntimeError("HUNI_AI_SECRET environment variable is mandatory for AI microservice")

    yield

    logger.info("Shutting down %s", settings.app_name)


app = FastAPI(
    title="HUNI AI Service",
    description="FastAPI Microservice for HUNI Property Management Application",
    version="0.1.0",
    docs_url=None,  # Disabled in production for attack surface reduction
    redoc_url=None,
    lifespan=lifespan,
)


@app.exception_handler(RequestValidationError)
async def validation_exception_handler(request: Request, exc: RequestValidationError) -> JSONResponse:
    """
    Custom validation error handler to ensure validation errors are returned
    cleanly without echoing raw sensitive input or headers.
    """
    sanitized_errors = []
    for err in exc.errors():
        field_loc = " -> ".join(str(loc) for loc in err.get("loc", []))
        sanitized_errors.append({
            "field": field_loc,
            "message": err.get("msg", "Invalid value"),
            "type": err.get("type", "validation_error"),
        })

    status_code = getattr(status, "HTTP_422_UNPROCESSABLE_CONTENT", 422)
    return JSONResponse(
        status_code=status_code,
        content={
            "status": "error",
            "message": "Payload validation failed",
            "errors": sanitized_errors,
        },
    )


# Register routers
app.include_router(health.router)
app.include_router(rag.router)
app.include_router(risk.router)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="127.0.0.1", port=8001, reload=True)
