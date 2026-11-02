import logging
from fastapi import APIRouter, Depends, status

from services.authentication import verify_service_secret
from services.delinquency_risk import (
    DelinquencyRiskEngine,
    RiskEvaluationRequest,
    RiskEvaluationResponse,
)

logger = logging.getLogger("huni.ai.risk")

router = APIRouter(
    prefix="/api/v1/risk",
    tags=["Delinquency Risk Engine"],
    dependencies=[Depends(verify_service_secret)],
)


@router.post(
    "/evaluate",
    response_model=RiskEvaluationResponse,
    status_code=status.HTTP_200_OK,
    summary="Evaluate Delinquency Risk for a Bill",
    description="Internal authorized endpoint that deterministically calculates an explainable delinquency risk score for a bill."
)
async def evaluate_risk(request: RiskEvaluationRequest) -> RiskEvaluationResponse:
    """
    Evaluates bill delinquency risk using transparent rule-based heuristics.
    Strictly isolated and calculated based on features provided at evaluation time.
    """
    logger.info(
        "Evaluating bill risk: bill_id=%d, property_id=%d, status=%s, as_of=%s",
        request.bill_id,
        request.property_id,
        request.status,
        request.evaluated_at.isoformat(),
    )

    response = DelinquencyRiskEngine.evaluate(request)

    logger.info(
        "Evaluated bill risk result: bill_id=%d, score=%.2f, tier=%s",
        response.bill_id,
        response.risk_score,
        response.risk_tier,
    )

    return response
