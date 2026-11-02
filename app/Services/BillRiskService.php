<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\DelinquencyRiskTier;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\BillRiskScore;
use App\Models\User;
use App\Services\Ai\HuniAiClient;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

class BillRiskService
{
    public function __construct(
        protected ?HuniAiClient $aiClient = null
    ) {
        $this->aiClient = $aiClient ?? app(HuniAiClient::class);
    }

    /**
     * Evaluate delinquency risk for a specific bill and optionally persist result.
     * Guaranteed to never throw unhandled exceptions or crash calling billing workflows
     * if AI service is offline, timed out, or unconfigured.
     */
    public function evaluateBill(
        Bill $bill,
        ?CarbonInterface $asOf = null,
        bool $persist = true
    ): ?BillRiskScore {
        $asOf = $asOf ? Carbon::instance($asOf) : now();

        $features = $this->extractFeatures($bill, $asOf);

        $response = $this->aiClient->evaluateRisk($features);

        if (! ($response['success'] ?? false) || ! isset($response['data'])) {
            Log::warning("HUNI AI risk evaluation skipped/failed for Bill #{$bill->id}: ".($response['error'] ?? 'Service unavailable'));

            return null;
        }

        $data = $response['data'];
        $tier = DelinquencyRiskTier::tryFrom((string) ($data['risk_tier'] ?? '')) ?? DelinquencyRiskTier::Rendah;

        $attributes = [
            'risk_score' => (float) ($data['risk_score'] ?? 0.0),
            'risk_tier' => $tier,
            'evaluator_type' => (string) ($data['evaluator_type'] ?? 'rule_based_heuristic'),
            'model_version' => (string) ($data['model_version'] ?? '1.0.0'),
            'contributing_factors' => (array) ($data['contributing_factors'] ?? []),
            'metadata' => (array) ($data['metadata'] ?? []),
            'evaluated_at' => Carbon::parse((string) ($data['evaluated_at'] ?? $asOf->toIso8601String())),
        ];

        if (! $persist) {
            $score = new BillRiskScore($attributes);
            $score->bill_id = $bill->id;

            return $score;
        }

        return BillRiskScore::updateOrCreate(
            ['bill_id' => $bill->id],
            $attributes
        );
    }

    /**
     * Extract features strictly as of evaluation timestamp without future data leakage.
     *
     * @return array<string, mixed>
     */
    public function extractFeatures(Bill $bill, CarbonInterface $asOf): array
    {
        $asOf = Carbon::instance($asOf);
        $propertyId = $bill->contract?->property_id ?? $bill->room?->property_id ?? 0;

        // 1. Calculate verified payments strictly up to asOf
        $verifiedPaidAmount = (float) $bill->payments()
            ->where('status', PaymentStatus::Berhasil->value)
            ->where(function ($query) use ($asOf) {
                $query->where('payment_date', '<=', $asOf)
                    ->orWhere('created_at', '<=', $asOf);
            })
            ->where('verified_at', '<=', $asOf)
            ->sum('amount');

        // 2. Check pending payments uploaded at or before asOf
        $hasPendingPayment = $bill->payments()
            ->where('status', PaymentStatus::MenungguVerifikasi->value)
            ->where('created_at', '<=', $asOf)
            ->exists();

        // 3. Determine effective status at evaluation time
        if ($bill->status === BillStatus::Dibatalkan->value) {
            $effectiveStatus = 'dibatalkan';
        } elseif ($verifiedPaidAmount >= (float) $bill->total_amount) {
            $effectiveStatus = 'lunas';
        } elseif ($verifiedPaidAmount > 0.0) {
            $effectiveStatus = 'sebagian';
        } elseif ($bill->due_date !== null && $bill->due_date->startOfDay()->isBefore($asOf->startOfDay())) {
            $effectiveStatus = 'jatuh_tempo';
        } else {
            $effectiveStatus = 'menunggu_pembayaran';
        }

        // 4. Historical behavior: Only matured previous bills strictly preceding this bill's issue date
        $pastBills = Bill::query()
            ->where('user_id', $bill->user_id)
            ->where('id', '!=', $bill->id)
            ->where('issue_date', '<', $bill->issue_date)
            ->where('due_date', '<=', $asOf->toDateString())
            ->where('status', '!=', BillStatus::Dibatalkan->value)
            ->get();

        $pastBillsCount = $pastBills->count();
        $pastLateBillsCount = 0;

        foreach ($pastBills as $pastBill) {
            // Did the tenant pay on time (on or before due_date)?
            $paidByDueDate = (float) $pastBill->payments()
                ->where('status', PaymentStatus::Berhasil->value)
                ->where(function ($query) use ($pastBill) {
                    $query->where('payment_date', '<=', $pastBill->due_date->endOfDay())
                        ->orWhere('created_at', '<=', $pastBill->due_date->endOfDay());
                })
                ->where('verified_at', '<=', $asOf)
                ->sum('amount');

            if ($paidByDueDate < (float) $pastBill->total_amount) {
                $pastLateBillsCount++;
            }
        }

        return [
            'bill_id' => $bill->id,
            'property_id' => $propertyId,
            'evaluated_at' => $asOf->toIso8601String(),
            'issue_date' => $bill->issue_date?->toDateString() ?? $asOf->toDateString(),
            'due_date' => $bill->due_date?->toDateString() ?? $asOf->toDateString(),
            'total_amount' => (float) $bill->total_amount,
            'paid_amount' => $verifiedPaidAmount,
            'status' => $effectiveStatus,
            'has_pending_payment' => $hasPendingPayment,
            'past_bills_count' => $pastBillsCount,
            'past_late_bills_count' => $pastLateBillsCount,
        ];
    }

    /**
     * Check whether an authenticated user is authorized to evaluate or view the risk score.
     */
    public function isAuthorizedOwner(User $user, Bill $bill): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        $property = $bill->contract?->property ?? $bill->room?->property;

        return $property !== null && $property->isOwnedBy($user);
    }
}
