<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class BillService
{
    public function __construct(
        protected ?NotificationService $notificationService = null
    ) {
        $this->notificationService = $notificationService ?? app(NotificationService::class);
    }

    /**
     * Generate an incremental globally unique invoice number in the official format INV-YYYYMM-XXXX.
     * Uses row locking and retry safety to prevent concurrency clashes.
     */
    public function generateInvoiceNumber(?CarbonInterface $date = null): string
    {
        $date = $date ? Carbon::instance($date) : now();
        $prefix = 'INV-'.$date->format('Ym').'-';

        return DB::transaction(function () use ($prefix) {
            // Find highest existing invoice number for the month under update lock
            $latest = Bill::where('bill_number', 'like', "{$prefix}%")
                ->orderByDesc('bill_number')
                ->lockForUpdate()
                ->value('bill_number');

            $sequence = 1;
            if ($latest && preg_match('/-(\d{4,})$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            $candidate = sprintf('%s%04d', $prefix, $sequence);
            while (Bill::where('bill_number', $candidate)->exists()) {
                $sequence++;
                $candidate = sprintf('%s%04d', $prefix, $sequence);
            }

            return $candidate;
        });
    }

    /**
     * Generate an incremental globally unique payment transaction number in the format PAY-YYYYMM-XXXX.
     * Uses row locking and retry safety to prevent concurrency clashes.
     */
    public function generatePaymentNumber(?CarbonInterface $date = null): string
    {
        $date = $date ? Carbon::instance($date) : now();
        $prefix = 'PAY-'.$date->format('Ym').'-';

        return DB::transaction(function () use ($prefix) {
            $latest = Payment::where('payment_number', 'like', "{$prefix}%")
                ->orderByDesc('payment_number')
                ->lockForUpdate()
                ->value('payment_number');

            $sequence = 1;
            if ($latest && preg_match('/-(\d{4,})$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            $candidate = sprintf('%s%04d', $prefix, $sequence);
            while (Payment::where('payment_number', $candidate)->exists()) {
                $sequence++;
                $candidate = sprintf('%s%04d', $prefix, $sequence);
            }

            return $candidate;
        });
    }

    /**
     * Normalize a date or string into canonical ISO billing period format (YYYY-MM).
     *
     * @throws InvalidArgumentException
     */
    public function normalizePeriod(CarbonInterface|string $period): string
    {
        if ($period instanceof CarbonInterface) {
            return $period->format('Y-m');
        }

        $trimmed = trim($period);

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $trimmed)) {
            return $trimmed;
        }

        try {
            $parsed = Carbon::parse($trimmed);

            return $parsed->format('Y-m');
        } catch (\Throwable $e) {
            throw new InvalidArgumentException("Format periode penagihan tidak valid: '{$period}'. Harus berupa YYYY-MM.");
        }
    }

    /**
     * Format ISO billing period (YYYY-MM) into Indonesian month name for presentation.
     */
    public function formatPeriod(string $period): string
    {
        $normalized = $this->normalizePeriod($period);
        [$year, $month] = explode('-', $normalized);

        return Carbon::createFromDate((int) $year, (int) $month, 1)
            ->locale('id')
            ->translatedFormat('F Y');
    }

    /**
     * Calculate invoice due date (seven calendar days after issue date).
     */
    public function calculateDueDate(CarbonInterface|string $issueDate): Carbon
    {
        $date = $issueDate instanceof CarbonInterface ? Carbon::instance($issueDate) : Carbon::parse($issueDate);

        return $date->copy()->addDays(7)->startOfDay();
    }

    /**
     * Calculate prorated rent using actual calendar days.
     *
     * Formula: prorated_rent = monthly_rent / days_in_month * billable_rental_days
     *
     * Rules:
     * - Contract start date and end date are included.
     * - Full calendar months use the full monthly rent.
     * - Uses exact calendar days in the month (including leap years: Feb 29 vs 28).
     * - Rounded to two decimal places.
     */
    public function calculateProratedRent(
        float $monthlyRent,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
        CarbonInterface|string $billingPeriod
    ): float {
        if ($monthlyRent <= 0) {
            return 0.0;
        }

        $period = $this->normalizePeriod($billingPeriod);
        [$year, $month] = explode('-', $period);

        $monthStart = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfDay();
        $daysInMonth = $monthStart->daysInMonth;
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();

        $contractStart = ($startDate instanceof CarbonInterface ? Carbon::instance($startDate) : Carbon::parse($startDate))->startOfDay();
        $contractEnd = ($endDate instanceof CarbonInterface ? Carbon::instance($endDate) : Carbon::parse($endDate))->startOfDay();

        // If contract does not overlap with this billing month
        if ($contractStart->greaterThan($monthEnd) || $contractEnd->lessThan($monthStart)) {
            return 0.0;
        }

        // Determine effective start and end dates within this calendar month
        $effectiveStart = $contractStart->greaterThan($monthStart) ? $contractStart : $monthStart;
        $effectiveEnd = $contractEnd->lessThan($monthEnd) ? $contractEnd : $monthEnd;

        // Billable rental days (both start and end dates inclusive)
        $billableDays = (int) $effectiveStart->diffInDays($effectiveEnd) + 1;

        if ($billableDays <= 0) {
            return 0.0;
        }

        // Full calendar month check: covers from the 1st to the last day of the month
        if ($billableDays >= $daysInMonth) {
            return (float) round($monthlyRent, 2);
        }

        // Prorated calculation using actual calendar days: (monthly_rent * billableDays) / daysInMonth
        $prorated = ($monthlyRent * $billableDays) / $daysInMonth;

        return (float) round($prorated, 2);
    }

    /**
     * Persist an invoice and its line items atomically.
     * Calculates the total amount on the server and ignores any client-supplied total.
     *
     * @param  array{
     *     contract_id?: int|null,
     *     user_id: int,
     *     room_id?: int|null,
     *     type?: string|BillType,
     *     deduplication_key?: string|null,
     *     billing_period: string,
     *     issue_date?: string|CarbonInterface|null,
     *     due_date?: string|CarbonInterface|null,
     *     notes?: string|null,
     *     status?: string|BillStatus,
     * }  $data
     * @param  list<array{description: string, amount: float|int|string, quantity?: int}>  $items
     */
    public function createInvoice(array $data, array $items): Bill
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Tagihan harus memiliki minimal satu item rincian tagihan.');
        }

        // Validate line items and calculate server-side total
        $calculatedTotal = 0.0;
        $validatedItems = [];

        foreach ($items as $item) {
            $amount = (float) ($item['amount'] ?? 0);
            $quantity = isset($item['quantity']) && (int) $item['quantity'] > 0 ? (int) $item['quantity'] : 1;

            if ($amount <= 0) {
                throw new InvalidArgumentException('Nominal setiap item tagihan harus lebih besar dari 0.');
            }

            $lineTotal = round($amount * $quantity, 2);
            $calculatedTotal += $lineTotal;

            $validatedItems[] = [
                'description' => trim((string) ($item['description'] ?? 'Item Tagihan')),
                'amount' => $amount,
                'quantity' => $quantity,
            ];
        }

        $issueDate = isset($data['issue_date'])
            ? ($data['issue_date'] instanceof CarbonInterface ? Carbon::instance($data['issue_date']) : Carbon::parse($data['issue_date']))
            : now();
        $billingPeriod = ! empty($data['billing_period']) ? $this->normalizePeriod($data['billing_period']) : $issueDate->format('Y-m');

        $dueDate = isset($data['due_date'])
            ? ($data['due_date'] instanceof CarbonInterface ? Carbon::instance($data['due_date']) : Carbon::parse($data['due_date']))
            : $this->calculateDueDate($issueDate);

        $type = $data['type'] ?? BillType::Sewa->value;
        if ($type instanceof BillType) {
            $type = $type->value;
        }

        $status = $data['status'] ?? BillStatus::MenungguPembayaran->value;
        if ($status instanceof BillStatus) {
            $status = $status->value;
        }

        $deduplicationKey = ! empty($data['deduplication_key']) ? trim((string) $data['deduplication_key']) : null;

        // Atomic creation with optimistic retries for invoice number conflicts
        return retry(5, function () use ($data, $validatedItems, $calculatedTotal, $billingPeriod, $issueDate, $dueDate, $type, $status, $deduplicationKey) {
            return DB::transaction(function () use ($data, $validatedItems, $calculatedTotal, $billingPeriod, $issueDate, $dueDate, $type, $status, $deduplicationKey) {
                // If a deduplication key is provided, check if an invoice already exists
                if ($deduplicationKey !== null) {
                    $existing = Bill::where('deduplication_key', $deduplicationKey)->first();
                    if ($existing) {
                        return $existing;
                    }
                }

                $invoiceNumber = $this->generateInvoiceNumber($issueDate);

                try {
                    $bill = Bill::create([
                        'bill_number' => $invoiceNumber,
                        'contract_id' => $data['contract_id'] ?? null,
                        'user_id' => $data['user_id'],
                        'room_id' => $data['room_id'] ?? null,
                        'type' => $type,
                        'deduplication_key' => $deduplicationKey,
                        'billing_period' => $billingPeriod,
                        'issue_date' => $issueDate->toDateString(),
                        'due_date' => $dueDate->toDateString(),
                        'total_amount' => $calculatedTotal,
                        'paid_amount' => 0.0,
                        'status' => $status,
                        'notes' => $data['notes'] ?? null,
                    ]);
                } catch (QueryException $e) {
                    if ($deduplicationKey !== null && ($e->errorInfo[1] ?? null) === 1062) {
                        $existing = Bill::where('deduplication_key', $deduplicationKey)->first();
                        if ($existing) {
                            return $existing->load('items');
                        }
                    }

                    throw $e;
                }

                foreach ($validatedItems as $item) {
                    BillItem::create([
                        'bill_id' => $bill->id,
                        'description' => $item['description'],
                        'amount' => $item['amount'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                return $bill->load('items');
            });
        }, 100);
    }

    /**
     * Generate the initial rent invoice and separate deposit invoice upon contract activation.
     * Idempotent and concurrency-safe.
     *
     * @return array{rent: ?Bill, deposit: ?Bill}
     */
    public function generateInitialInvoices(Contract $contract): array
    {
        if (! $contract->isActive()) {
            return ['rent' => null, 'deposit' => null];
        }

        $period = $contract->start_date ? $contract->start_date->format('Y-m') : now()->format('Y-m');
        $issueDate = $contract->start_date ?? now();
        $roomNumber = $contract->room?->room_number ?? (string) $contract->room_id;

        // 1. Initial Rent Invoice
        $rentAmount = $this->calculateProratedRent(
            (float) $contract->monthly_rent,
            $contract->start_date,
            $contract->end_date,
            $period
        );

        $rentDeduplicationKey = "rent:{$contract->id}:{$period}";
        $existingRentBill = Bill::where('deduplication_key', $rentDeduplicationKey)->first();

        $rentBill = $existingRentBill;
        if (! $rentBill && $rentAmount > 0) {
            $formattedPeriod = $this->formatPeriod($period);
            $rentBill = $this->createInvoice(
                [
                    'contract_id' => $contract->id,
                    'user_id' => $contract->user_id,
                    'room_id' => $contract->room_id,
                    'type' => BillType::Sewa,
                    'deduplication_key' => $rentDeduplicationKey,
                    'billing_period' => $period,
                    'issue_date' => $issueDate,
                    'due_date' => $this->calculateDueDate($issueDate),
                    'notes' => "Tagihan sewa kamar {$roomNumber} untuk periode {$formattedPeriod} (Kontrak {$contract->contract_number}).",
                ],
                [
                    [
                        'description' => "Sewa Kamar {$roomNumber} - Periode {$formattedPeriod}",
                        'amount' => $rentAmount,
                        'quantity' => 1,
                    ],
                ]
            );
        }

        // 2. Separate Deposit Invoice (only if deposit_amount > 0)
        $depositBill = null;
        if ((float) $contract->deposit_amount > 0) {
            $depositDeduplicationKey = "deposit:{$contract->id}";
            $existingDepositBill = Bill::where('deduplication_key', $depositDeduplicationKey)->first();

            $depositBill = $existingDepositBill;
            if (! $depositBill) {
                $depositBill = $this->createInvoice(
                    [
                        'contract_id' => $contract->id,
                        'user_id' => $contract->user_id,
                        'room_id' => $contract->room_id,
                        'type' => BillType::Deposit,
                        'deduplication_key' => $depositDeduplicationKey,
                        'billing_period' => $period,
                        'issue_date' => $issueDate,
                        'due_date' => $this->calculateDueDate($issueDate),
                        'notes' => "Uang jaminan (deposit) sewa kamar {$roomNumber} (Kontrak {$contract->contract_number}).",
                    ],
                    [
                        [
                            'description' => "Deposit Jaminan Sewa - Kamar {$roomNumber}",
                            'amount' => (float) $contract->deposit_amount,
                            'quantity' => 1,
                        ],
                    ]
                );
            }
        }

        return [
            'rent' => $rentBill,
            'deposit' => $depositBill,
        ];
    }

    /**
     * Generate a recurring monthly rent invoice for an active contract.
     * Only eligible active contracts receive recurring invoices.
     * Cancelled and completed contracts are strictly ignored.
     */
    public function generateRecurringRentInvoice(Contract $contract, string $billingPeriod): ?Bill
    {
        // Cancelled or completed contracts must not receive future recurring invoices
        if (! $contract->isActive()) {
            return null;
        }

        $period = $this->normalizePeriod($billingPeriod);
        [$year, $month] = explode('-', $period);

        $monthStart = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();

        $contractStart = $contract->start_date ? Carbon::instance($contract->start_date)->startOfDay() : null;
        $contractEnd = $contract->end_date ? Carbon::instance($contract->end_date)->startOfDay() : null;

        // Check if billing period is within the contract duration
        if (! $contractStart || ! $contractEnd) {
            return null;
        }

        if ($monthEnd->lessThan($contractStart) || $monthStart->greaterThan($contractEnd)) {
            return null;
        }

        $deduplicationKey = "rent:{$contract->id}:{$period}";

        // Idempotency check: if invoice already exists, return it safely
        $existing = Bill::where('deduplication_key', $deduplicationKey)->first();
        if ($existing) {
            return $existing;
        }

        $rentAmount = $this->calculateProratedRent(
            (float) $contract->monthly_rent,
            $contractStart,
            $contractEnd,
            $period
        );

        if ($rentAmount <= 0) {
            return null;
        }

        $roomNumber = $contract->room?->room_number ?? (string) $contract->room_id;
        $formattedPeriod = $this->formatPeriod($period);

        // Issue date is either 1st of billing month or contract start date if later
        $issueDate = $monthStart->greaterThan($contractStart) ? $monthStart : $contractStart;

        return $this->createInvoice(
            [
                'contract_id' => $contract->id,
                'user_id' => $contract->user_id,
                'room_id' => $contract->room_id,
                'type' => BillType::Sewa,
                'deduplication_key' => $deduplicationKey,
                'billing_period' => $period,
                'issue_date' => $issueDate,
                'due_date' => $this->calculateDueDate($issueDate),
                'notes' => "Tagihan sewa bulanan kamar {$roomNumber} periode {$formattedPeriod} (Kontrak {$contract->contract_number}).",
            ],
            [
                [
                    'description' => "Sewa Kamar {$roomNumber} - Periode {$formattedPeriod}",
                    'amount' => $rentAmount,
                    'quantity' => 1,
                ],
            ]
        );
    }

    /**
     * Generate an additional or ad-hoc invoice linked to a contract.
     * Historical (completed or cancelled) contracts may also support valid additional charges.
     */
    public function generateAdditionalInvoice(
        Contract $contract,
        string $description,
        float $amount,
        ?string $billingPeriod = null,
        ?string $notes = null
    ): Bill {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal tagihan tambahan harus lebih besar dari 0.');
        }

        $period = $billingPeriod ? $this->normalizePeriod($billingPeriod) : now()->format('Y-m');
        $issueDate = now();
        $roomNumber = $contract->room?->room_number ?? (string) $contract->room_id;

        return $this->createInvoice(
            [
                'contract_id' => $contract->id,
                'user_id' => $contract->user_id,
                'room_id' => $contract->room_id,
                'type' => BillType::Tambahan,
                'deduplication_key' => null, // Additional invoices have null deduplication key to permit multiple ad-hoc charges
                'billing_period' => $period,
                'issue_date' => $issueDate,
                'due_date' => $this->calculateDueDate($issueDate),
                'notes' => $notes ?? "Biaya tambahan untuk kamar {$roomNumber} (Kontrak {$contract->contract_number}).",
            ],
            [
                [
                    'description' => trim($description),
                    'amount' => $amount,
                    'quantity' => 1,
                ],
            ]
        );
    }

    /**
     * Approve a pending payment, update bill balance and status atomically,
     * and synchronize contract deposit status if the bill is a fully-paid deposit.
     *
     * @throws ValidationException
     */
    public function approvePayment(Payment $payment, User $owner): Payment
    {
        $approvedPayment = DB::transaction(function () use ($payment, $owner) {
            // Lock payment record for update
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            // Lock associated bill for update
            /** @var Bill $lockedBill */
            $lockedBill = Bill::where('id', $lockedPayment->bill_id)->lockForUpdate()->firstOrFail();

            // Verify owner authorization over the property
            $property = $lockedBill->contract?->property ?? $lockedBill->room?->property;
            if (! $property || ! $property->isOwnedBy($owner)) {
                throw ValidationException::withMessages([
                    'payment' => 'Anda tidak memiliki hak akses untuk memverifikasi pembayaran ini.',
                ]);
            }

            // Only pending payments can be approved
            if (! $lockedPayment->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Hanya pembayaran dengan status menunggu verifikasi yang dapat disetujui.',
                ]);
            }

            // Check if bill is already cancelled
            if ($lockedBill->isCancelled()) {
                throw ValidationException::withMessages([
                    'payment' => 'Tagihan telah dibatalkan, pembayaran tidak dapat disetujui.',
                ]);
            }

            // Re-validate outstanding balance under lock to strictly prevent overpayment
            $paymentAmount = (float) $lockedPayment->amount;
            $currentOutstanding = $lockedBill->outstandingAmount();

            if ($paymentAmount > $currentOutstanding) {
                throw ValidationException::withMessages([
                    'payment' => 'Nominal pembayaran (Rp '.number_format($paymentAmount, 0, ',', '.').') melebihi sisa tagihan saat ini (Rp '.number_format($currentOutstanding, 0, ',', '.').').',
                ]);
            }

            // Record verification strictly using server state
            $now = now();
            $lockedPayment->update([
                'status' => PaymentStatus::Berhasil->value,
                'verified_by' => $owner->id,
                'verified_at' => $now,
            ]);

            // Add payment amount to bills.paid_amount exactly once
            $newPaidAmount = round((float) $lockedBill->paid_amount + $paymentAmount, 2);
            $isPaidInFull = $newPaidAmount >= (float) $lockedBill->total_amount;
            $newStatus = $isPaidInFull ? BillStatus::Lunas->value : BillStatus::Sebagian->value;

            $lockedBill->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
            ]);

            // If this is a deposit invoice and now paid in full, synchronize contract deposit_status
            if ($lockedBill->type === BillType::Deposit->value && $isPaidInFull && $lockedBill->contract_id) {
                Contract::where('id', $lockedBill->contract_id)->update([
                    'deposit_status' => 'lunas',
                ]);
            }

            return $lockedPayment->fresh(['bill.user', 'verifier', 'proofs']);
        });

        // Safely notify tenant that payment has been verified
        $this->notificationService->notifyPaymentVerified($approvedPayment);

        return $approvedPayment;
    }

    /**
     * Reject a pending payment with a mandatory rejection reason.
     * Leaves bills.paid_amount completely untouched and preserves proof history.
     *
     * @throws ValidationException
     */
    public function rejectPayment(Payment $payment, User $owner, string $rejectionReason): Payment
    {
        $trimmedReason = trim($rejectionReason);
        if (mb_strlen($trimmedReason) < 5) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Alasan penolakan pembayaran minimal 5 karakter.',
            ]);
        }

        $rejectedPayment = DB::transaction(function () use ($payment, $owner, $trimmedReason) {
            // Lock payment record for update
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            // Lock associated bill for update
            /** @var Bill $lockedBill */
            $lockedBill = Bill::where('id', $lockedPayment->bill_id)->lockForUpdate()->firstOrFail();

            // Verify owner authorization over the property
            $property = $lockedBill->contract?->property ?? $lockedBill->room?->property;
            if (! $property || ! $property->isOwnedBy($owner)) {
                throw ValidationException::withMessages([
                    'payment' => 'Anda tidak memiliki hak akses untuk menolak pembayaran ini.',
                ]);
            }

            // Only pending payments can be rejected
            if (! $lockedPayment->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Hanya pembayaran dengan status menunggu verifikasi yang dapat ditolak.',
                ]);
            }

            // Record rejection strictly using server state
            $now = now();
            $lockedPayment->update([
                'status' => PaymentStatus::Ditolak->value,
                'rejection_reason' => $trimmedReason,
                'verified_by' => $owner->id,
                'verified_at' => $now,
            ]);

            // bills.paid_amount remains untouched!

            return $lockedPayment->fresh(['bill.user', 'verifier', 'proofs']);
        });

        // Safely notify tenant that payment has been rejected
        $this->notificationService->notifyPaymentRejected($rejectedPayment);

        return $rejectedPayment;
    }
}
