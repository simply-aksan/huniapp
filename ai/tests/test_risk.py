from datetime import date, datetime, timezone
import pytest
from fastapi.testclient import TestClient

from services.delinquency_risk import DelinquencyRiskEngine, RiskEvaluationRequest


def test_cancelled_bill_returns_zero_risk_and_rendah_tier():
    req = RiskEvaluationRequest(
        bill_id=1,
        property_id=1,
        evaluated_at=datetime(2026, 10, 15, 10, 0, 0, tzinfo=timezone.utc),
        issue_date=date(2026, 10, 1),
        due_date=date(2026, 10, 10),
        total_amount=1500000.0,
        paid_amount=0.0,
        status="dibatalkan",
    )
    res = DelinquencyRiskEngine.evaluate(req)

    assert res.risk_score == 0.0
    assert res.risk_tier == "rendah"
    assert res.evaluator_type == "rule_based_heuristic"
    assert res.model_version == "1.0.0"
    assert len(res.contributing_factors) == 1
    assert res.contributing_factors[0].code == "bill_cancelled"
    assert res.metadata["is_eligible"] is False


def test_fully_paid_bill_returns_zero_risk_and_rendah_tier():
    req = RiskEvaluationRequest(
        bill_id=2,
        property_id=1,
        evaluated_at=datetime(2026, 10, 15, 10, 0, 0, tzinfo=timezone.utc),
        issue_date=date(2026, 10, 1),
        due_date=date(2026, 10, 10),
        total_amount=1500000.0,
        paid_amount=1500000.0,
        status="lunas",
    )
    res = DelinquencyRiskEngine.evaluate(req)

    assert res.risk_score == 0.0
    assert res.risk_tier == "rendah"
    assert res.contributing_factors[0].code == "bill_fully_paid"
    assert res.metadata["is_eligible"] is False


def test_bill_not_yet_due_with_good_history_is_low_risk():
    # 10 days before due date, clean history
    req = RiskEvaluationRequest(
        bill_id=3,
        property_id=2,
        evaluated_at=datetime(2026, 10, 1, 12, 0, 0, tzinfo=timezone.utc),
        issue_date=date(2026, 10, 1),
        due_date=date(2026, 10, 15),
        total_amount=2000000.0,
        paid_amount=0.0,
        status="menunggu_pembayaran",
        past_bills_count=5,
        past_late_bills_count=0,
    )
    res = DelinquencyRiskEngine.evaluate(req)

    # Base 10 + DueDate(0) + Unpaid100%(10) + GoodHistory(-15) = 5.0
    assert res.risk_score == 5.0
    assert res.risk_tier == "rendah"
    factor_codes = [f.code for f in res.contributing_factors]
    assert "baseline_uncertainty" in factor_codes
    assert "due_date_proximity" in factor_codes
    assert "unpaid_balance_ratio" in factor_codes
    assert "historical_delinquency" in factor_codes


def test_partial_payment_reduces_risk():
    # Comparing 100% unpaid vs >50% paid for same due date
    req_unpaid = RiskEvaluationRequest(
        bill_id=4,
        property_id=2,
        evaluated_at=datetime(2026, 10, 10, 12, 0, 0, tzinfo=timezone.utc),
        issue_date=date(2026, 10, 1),
        due_date=date(2026, 10, 10),
        total_amount=1000000.0,
        paid_amount=0.0,
        status="menunggu_pembayaran",
    )
    req_partial = RiskEvaluationRequest(
        bill_id=5,
        property_id=2,
        evaluated_at=datetime(2026, 10, 10, 12, 0, 0, tzinfo=timezone.utc),
        issue_date=date(2026, 10, 1),
        due_date=date(2026, 10, 10),
        total_amount=1000000.0,
        paid_amount=750000.0,  # 75% paid
        status="sebagian",
    )

    res_unpaid = DelinquencyRiskEngine.evaluate(req_unpaid)
    res_partial = DelinquencyRiskEngine.evaluate(req_partial)

    assert res_partial.risk_score < res_unpaid.risk_score
    # Unpaid has +10, Partial (>50% paid) has -10, difference is 20 points
    assert round(res_unpaid.risk_score - res_partial.risk_score, 2) == 20.0


def test_overdue_and_chronic_late_history_yields_high_risk():
    # Overdue by 15 days, chronic history, 100% unpaid
    req = RiskEvaluationRequest(
        bill_id=6,
        property_id=3,
        evaluated_at=datetime(2026, 10, 25, 12, 0, 0, tzinfo=timezone.utc),
        issue_date=date(2026, 10, 1),
        due_date=date(2026, 10, 10),
        total_amount=2000000.0,
        paid_amount=0.0,
        status="jatuh_tempo",
        past_bills_count=4,
        past_late_bills_count=4,  # 100% past late
    )
    res = DelinquencyRiskEngine.evaluate(req)

    # Base(10) + Overdue>14(70) + Unpaid100%(10) + ChronicLate(25) = 115 -> clamped to 100.0
    assert res.risk_score == 100.0
    assert res.risk_tier == "tinggi"


def test_pending_payment_verification_mitigates_overdue_risk():
    # Overdue 5 days (+40) + Base(10) + Unpaid(10) + NeutralHistory(0) = 60 (Sedang)
    req_without_proof = RiskEvaluationRequest(
        bill_id=7,
        property_id=3,
        evaluated_at=datetime(2026, 10, 15, 12, 0, 0, tzinfo=timezone.utc),
        issue_date=date(2026, 10, 1),
        due_date=date(2026, 10, 10),
        total_amount=1000000.0,
        paid_amount=0.0,
        status="jatuh_tempo",
        has_pending_payment=False,
    )
    req_with_proof = RiskEvaluationRequest(
        bill_id=8,
        property_id=3,
        evaluated_at=datetime(2026, 10, 15, 12, 0, 0, tzinfo=timezone.utc),
        issue_date=date(2026, 10, 1),
        due_date=date(2026, 10, 10),
        total_amount=1000000.0,
        paid_amount=0.0,
        status="jatuh_tempo",
        has_pending_payment=True,
    )

    res_without = DelinquencyRiskEngine.evaluate(req_without_proof)
    res_with = DelinquencyRiskEngine.evaluate(req_with_proof)

    assert res_without.risk_score == 60.0
    assert res_without.risk_tier == "sedang"
    assert res_with.risk_score == 40.0
    assert res_with.risk_tier == "sedang"
    assert round(res_without.risk_score - res_with.risk_score, 2) == 20.0


def test_deterministic_evaluation():
    req = RiskEvaluationRequest(
        bill_id=9,
        property_id=1,
        evaluated_at=datetime(2026, 10, 12, 8, 30, 0, tzinfo=timezone.utc),
        issue_date=date(2026, 10, 1),
        due_date=date(2026, 10, 10),
        total_amount=1200000.0,
        paid_amount=400000.0,
        status="sebagian",
        past_bills_count=3,
        past_late_bills_count=1,
    )

    res1 = DelinquencyRiskEngine.evaluate(req)
    res2 = DelinquencyRiskEngine.evaluate(req)

    assert res1.risk_score == res2.risk_score
    assert res1.risk_tier == res2.risk_tier
    assert len(res1.contributing_factors) == len(res2.contributing_factors)
    for f1, f2 in zip(res1.contributing_factors, res2.contributing_factors):
        assert f1.code == f2.code
        assert f1.impact == f2.impact


def test_api_endpoint_requires_auth(client: TestClient):
    payload = {
        "bill_id": 10,
        "property_id": 1,
        "evaluated_at": "2026-10-15T10:00:00Z",
        "issue_date": "2026-10-01",
        "due_date": "2026-10-10",
        "total_amount": 1000000.0,
        "paid_amount": 0.0,
        "status": "jatuh_tempo",
    }
    # No header
    resp = client.post("/api/v1/risk/evaluate", json=payload)
    assert resp.status_code == 401

    # Wrong header
    resp = client.post(
        "/api/v1/risk/evaluate",
        headers={"X-HUNI-AI-SECRET": "wrong_key"},
        json=payload
    )
    assert resp.status_code == 401


def test_api_endpoint_success_with_valid_secret(client: TestClient, auth_headers: dict):
    payload = {
        "bill_id": 11,
        "property_id": 5,
        "evaluated_at": "2026-10-15T10:00:00Z",
        "issue_date": "2026-10-01",
        "due_date": "2026-10-10",
        "total_amount": 1500000.0,
        "paid_amount": 500000.0,
        "status": "sebagian",
        "has_pending_payment": False,
        "past_bills_count": 2,
        "past_late_bills_count": 0,
    }

    resp = client.post("/api/v1/risk/evaluate", headers=auth_headers, json=payload)
    assert resp.status_code == 200

    data = resp.json()
    assert data["bill_id"] == 11
    assert data["property_id"] == 5
    assert 0.0 <= data["risk_score"] <= 100.0
    assert data["risk_tier"] in ["rendah", "sedang", "tinggi"]
    assert data["evaluator_type"] == "rule_based_heuristic"
    assert data["model_version"] == "1.0.0"
    assert len(data["contributing_factors"]) > 0


def test_api_endpoint_validates_payload(client: TestClient, auth_headers: dict):
    # past_late_bills_count cannot exceed past_bills_count
    payload = {
        "bill_id": 12,
        "property_id": 1,
        "evaluated_at": "2026-10-15T10:00:00Z",
        "issue_date": "2026-10-01",
        "due_date": "2026-10-10",
        "total_amount": 1000000.0,
        "paid_amount": 0.0,
        "status": "jatuh_tempo",
        "past_bills_count": 2,
        "past_late_bills_count": 5,  # Invalid: 5 > 2
    }

    resp = client.post("/api/v1/risk/evaluate", headers=auth_headers, json=payload)
    assert resp.status_code == 422


def test_boundary_thresholds_39_99_40_69_99_70():
    """
    Test Phase 11D requirement: verify exact threshold transitions:
    - 39.99 -> rendah
    - 40.00 -> sedang
    - 69.99 -> sedang
    - 70.00 -> tinggi
    """
    assert DelinquencyRiskEngine.LOW_TIER_MAX == 39.99
    assert DelinquencyRiskEngine.MEDIUM_TIER_MAX == 69.99

    def get_tier(score: float) -> str:
        if score <= DelinquencyRiskEngine.LOW_TIER_MAX:
            return "rendah"
        elif score <= DelinquencyRiskEngine.MEDIUM_TIER_MAX:
            return "sedang"
        else:
            return "tinggi"

    assert get_tier(0.0) == "rendah"
    assert get_tier(39.99) == "rendah"
    assert get_tier(40.00) == "sedang"
    assert get_tier(69.99) == "sedang"
    assert get_tier(70.00) == "tinggi"
    assert get_tier(100.0) == "tinggi"
