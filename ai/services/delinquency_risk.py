from datetime import date, datetime
from typing import List, Literal, Optional
from pydantic import BaseModel, Field, model_validator


class RiskEvaluationRequest(BaseModel):
    """
    Input payload for rule-based delinquency risk evaluation.
    All features must be computable from data available strictly as of `evaluated_at`.
    """
    bill_id: int = Field(gt=0, description="Unique Bill ID")
    property_id: int = Field(gt=0, description="Property ID")
    evaluated_at: datetime = Field(description="Strict evaluation timestamp (as-of)")
    issue_date: date = Field(description="Bill issue date")
    due_date: date = Field(description="Bill due date")
    total_amount: float = Field(gt=0, description="Total billed amount")
    paid_amount: float = Field(ge=0, description="Verified paid amount at evaluation time")
    status: Literal["menunggu_pembayaran", "sebagian", "lunas", "jatuh_tempo", "dibatalkan"] = Field(
        description="Bill status at evaluation time"
    )
    has_pending_payment: bool = Field(
        default=False,
        description="Whether a payment submission is awaiting owner verification at evaluation time"
    )
    past_bills_count: int = Field(
        default=0,
        ge=0,
        description="Count of previous matured bills for this tenant prior to current bill issue date"
    )
    past_late_bills_count: int = Field(
        default=0,
        ge=0,
        description="Count of previous bills paid late or currently unpaid past due date"
    )

    @model_validator(mode="after")
    def validate_counts_and_amounts(self) -> "RiskEvaluationRequest":
        if self.past_late_bills_count > self.past_bills_count:
            raise ValueError("past_late_bills_count cannot exceed past_bills_count")
        return self


class ContributingFactor(BaseModel):
    """
    Transparent explanation of an individual feature's impact on the final risk score.
    """
    code: str = Field(description="Machine-readable factor code")
    factor: str = Field(description="Human-readable factor title")
    impact: float = Field(description="Point contribution (+ or -) to the risk score")
    description: str = Field(description="Detailed explanation of the calculation rationale")


class RiskEvaluationResponse(BaseModel):
    """
    Output payload containing the transparent risk score, tier, and factors.
    """
    bill_id: int
    property_id: int
    risk_score: float = Field(ge=0.0, le=100.0, description="Heuristic delinquency risk score (0-100)")
    risk_tier: Literal["rendah", "sedang", "tinggi"] = Field(description="Categorical risk tier")
    evaluator_type: str = "rule_based_heuristic"
    model_version: str = "1.0.0"
    contributing_factors: List[ContributingFactor]
    metadata: dict
    evaluated_at: datetime


class DelinquencyRiskEngine:
    """
    Deterministic Rule-Based Delinquency Risk Engine (v1.0.0).
    Computes explainable risk scores without black-box ML or unvalidated external correlations.
    """
    MODEL_VERSION = "1.0.0"
    EVALUATOR_TYPE = "rule_based_heuristic"

    # Score threshold boundaries matching DelinquencyRiskTier
    LOW_TIER_MAX = 39.99
    MEDIUM_TIER_MAX = 69.99

    @classmethod
    def evaluate(cls, req: RiskEvaluationRequest) -> RiskEvaluationResponse:
        effective_paid = min(req.paid_amount, req.total_amount)
        outstanding_amount = max(0.0, round(req.total_amount - effective_paid, 2))

        # 1. Short-circuit: Cancelled bill (Zero risk obligation)
        if req.status == "dibatalkan":
            return RiskEvaluationResponse(
                bill_id=req.bill_id,
                property_id=req.property_id,
                risk_score=0.0,
                risk_tier="rendah",
                evaluator_type=cls.EVALUATOR_TYPE,
                model_version=cls.MODEL_VERSION,
                contributing_factors=[
                    ContributingFactor(
                        code="bill_cancelled",
                        factor="Tagihan Dibatalkan",
                        impact=0.0,
                        description="Tagihan telah dibatalkan secara sah. Tidak ada kewajiban pembayaran aktif."
                    )
                ],
                metadata={
                    "is_eligible": False,
                    "reason": "cancelled",
                    "outstanding_amount": 0.0,
                    "total_amount": req.total_amount,
                },
                evaluated_at=req.evaluated_at,
            )

        # 2. Short-circuit: Fully paid bill as of evaluation time
        if req.status == "lunas" or outstanding_amount <= 0.0:
            return RiskEvaluationResponse(
                bill_id=req.bill_id,
                property_id=req.property_id,
                risk_score=0.0,
                risk_tier="rendah",
                evaluator_type=cls.EVALUATOR_TYPE,
                model_version=cls.MODEL_VERSION,
                contributing_factors=[
                    ContributingFactor(
                        code="bill_fully_paid",
                        factor="Tagihan Lunas",
                        impact=0.0,
                        description="Tagihan telah dibayar lunas pada waktu evaluasi. Risiko keterlambatan adalah 0."
                    )
                ],
                metadata={
                    "is_eligible": False,
                    "reason": "paid",
                    "outstanding_amount": 0.0,
                    "total_amount": req.total_amount,
                    "paid_amount": effective_paid,
                },
                evaluated_at=req.evaluated_at,
            )

        # 3. Active unpaid/partially paid bill evaluation
        factors: List[ContributingFactor] = []

        # Baseline natural uncertainty for active bills
        factors.append(
            ContributingFactor(
                code="baseline_uncertainty",
                factor="Baseline Tagihan Aktif",
                impact=10.0,
                description="Ketidakpastian alami awal untuk tagihan aktif yang belum lunas."
            )
        )

        # Due Date Analysis
        as_of_date = req.evaluated_at.date()
        delta_days = (as_of_date - req.due_date).days

        if delta_days > 0:
            days_overdue = delta_days
            if days_overdue <= 3:
                impact = 25.0
                desc = f"Tagihan telah melewati jatuh tempo {req.due_date} sebanyak {days_overdue} hari (keterlambatan ringan)."
            elif days_overdue <= 7:
                impact = 40.0
                desc = f"Tagihan telah melewati jatuh tempo {req.due_date} sebanyak {days_overdue} hari (keterlambatan sedang)."
            elif days_overdue <= 14:
                impact = 55.0
                desc = f"Tagihan telah melewati jatuh tempo {req.due_date} sebanyak {days_overdue} hari (keterlambatan signifikan 1-2 minggu)."
            else:
                impact = 70.0
                desc = f"Tagihan telah melewati jatuh tempo {req.due_date} sebanyak {days_overdue} hari (keterlambatan kritis > 2 minggu)."

            factors.append(
                ContributingFactor(
                    code="overdue_severity",
                    factor="Tingkat Keterlambatan Jatuh Tempo",
                    impact=impact,
                    description=desc,
                )
            )
        else:
            days_until_due = abs(delta_days)
            if days_until_due == 0:
                impact = 15.0
                desc = "Hari ini merupakan tanggal jatuh tempo tagihan."
            elif days_until_due <= 2:
                impact = 10.0
                desc = f"Mendekati tanggal jatuh tempo dalam {days_until_due} hari."
            elif days_until_due <= 7:
                impact = 5.0
                desc = f"Mendekati tanggal jatuh tempo dalam {days_until_due} hari."
            else:
                impact = 0.0
                desc = f"Jatuh tempo masih cukup lama ({days_until_due} hari lagi). Tidak ada penalti jatuh tempo."

            factors.append(
                ContributingFactor(
                    code="due_date_proximity",
                    factor="Kedekatan Tanggal Jatuh Tempo",
                    impact=impact,
                    description=desc,
                )
            )

        # Unpaid balance ratio analysis
        unpaid_ratio = outstanding_amount / req.total_amount
        if unpaid_ratio >= 0.999:
            impact = 10.0
            desc = "Belum ada pembayaran cicilan atau pembayaran parsial sama sekali (100% outstanding)."
        elif unpaid_ratio > 0.50:
            impact = 5.0
            paid_pct = round((1.0 - unpaid_ratio) * 100)
            desc = f"Sebagian kecil tagihan telah dibayar ({paid_pct}%), sisa tagihan masih di atas 50%."
        else:
            impact = -10.0
            paid_pct = round((1.0 - unpaid_ratio) * 100)
            desc = f"Mayoritas tagihan telah dibayar ({paid_pct}%), sisa tagihan relatif kecil."

        factors.append(
            ContributingFactor(
                code="unpaid_balance_ratio",
                factor="Rasio Sisa Tagihan",
                impact=impact,
                description=desc,
            )
        )

        # Pending payment verification analysis
        if req.has_pending_payment:
            factors.append(
                ContributingFactor(
                    code="pending_payment_verification",
                    factor="Pembayaran Menunggu Verifikasi",
                    impact=-20.0,
                    description="Tenant telah mengunggah bukti pembayaran yang saat ini menunggu konfirmasi pemilik kos."
                )
            )

        # Historical tenant payment behavior analysis
        if req.past_bills_count == 0:
            factors.append(
                ContributingFactor(
                    code="historical_tenancy",
                    factor="Riwayat Penyewa Baru",
                    impact=0.0,
                    description="Penyewa baru tanpa riwayat tagihan terdahulu (faktor historis diperlakukan netral)."
                )
            )
        else:
            late_ratio = req.past_late_bills_count / req.past_bills_count
            if req.past_late_bills_count == 0:
                impact = -15.0
                desc = f"Riwayat disiplin: selalu membayar tepat waktu pada seluruh {req.past_bills_count} tagihan terdahulu."
            elif late_ratio <= 0.33:
                impact = 5.0
                pct = round(late_ratio * 100)
                desc = f"Pernah terlambat pada {req.past_late_bills_count} dari {req.past_bills_count} tagihan terdahulu ({pct}%)."
            elif late_ratio <= 0.66:
                impact = 15.0
                pct = round(late_ratio * 100)
                desc = f"Cukup sering terlambat pada {req.past_late_bills_count} dari {req.past_bills_count} tagihan terdahulu ({pct}%)."
            else:
                impact = 25.0
                pct = round(late_ratio * 100)
                desc = f"Riwayat kronis: terlambat pada {req.past_late_bills_count} dari {req.past_bills_count} tagihan terdahulu ({pct}%)."

            factors.append(
                ContributingFactor(
                    code="historical_delinquency",
                    factor="Riwayat Keterlambatan Terdahulu",
                    impact=impact,
                    description=desc,
                )
            )

        # Sum of factor impacts and clamp to [0.0, 100.0]
        raw_score = sum(f.impact for f in factors)
        final_score = round(max(0.0, min(100.0, raw_score)), 2)

        if final_score <= cls.LOW_TIER_MAX:
            tier = "rendah"
        elif final_score <= cls.MEDIUM_TIER_MAX:
            tier = "sedang"
        else:
            tier = "tinggi"

        metadata = {
            "is_eligible": True,
            "raw_score": round(raw_score, 2),
            "total_amount": req.total_amount,
            "paid_amount": effective_paid,
            "outstanding_amount": outstanding_amount,
            "unpaid_ratio": round(unpaid_ratio, 4),
            "days_overdue": max(0, delta_days),
            "days_until_due": max(0, -delta_days),
            "has_pending_payment": req.has_pending_payment,
            "past_bills_count": req.past_bills_count,
            "past_late_bills_count": req.past_late_bills_count,
        }

        return RiskEvaluationResponse(
            bill_id=req.bill_id,
            property_id=req.property_id,
            risk_score=final_score,
            risk_tier=tier,
            evaluator_type=cls.EVALUATOR_TYPE,
            model_version=cls.MODEL_VERSION,
            contributing_factors=factors,
            metadata=metadata,
            evaluated_at=req.evaluated_at,
        )
