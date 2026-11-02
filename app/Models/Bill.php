<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bill extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bill_number',
        'contract_id',
        'user_id',
        'room_id',
        'type',
        'deduplication_key',
        'billing_period',
        'issue_date',
        'due_date',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    /**
     * Interact with the bill's status, accepting both enum instances and string values.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (string) ($value ?? BillStatus::MenungguPembayaran->value),
            set: fn ($value) => $value instanceof BillStatus ? $value->value : (string) $value,
        );
    }

    /**
     * Interact with the bill's type, accepting both enum instances and string values.
     */
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (string) ($value ?? BillType::Sewa->value),
            set: fn ($value) => $value instanceof BillType ? $value->value : (string) $value,
        );
    }

    /**
     * Get the strongly-typed BillStatus enum instance.
     */
    public function statusEnum(): ?BillStatus
    {
        return BillStatus::tryFrom($this->status);
    }

    /**
     * Get the strongly-typed BillType enum instance.
     */
    public function typeEnum(): ?BillType
    {
        return BillType::tryFrom($this->type);
    }

    /**
     * Contract under which this bill was generated.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Tenant user responsible for paying this bill.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Room associated with this bill.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Line items in this bill.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    /**
     * Payment attempts for this bill.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * AI delinquency risk evaluation score for this bill.
     */
    public function riskScore(): HasOne
    {
        return $this->hasOne(BillRiskScore::class);
    }

    /**
     * Determine if this bill is fully paid.
     */
    public function isPaid(): bool
    {
        return $this->status === BillStatus::Lunas->value;
    }

    /**
     * Determine if this bill has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === BillStatus::Dibatalkan->value;
    }

    /**
     * Determine if this bill is past its due date without being paid.
     */
    public function isOverdue(): bool
    {
        if ($this->isPaid() || $this->isCancelled()) {
            return false;
        }

        return $this->due_date !== null && $this->due_date->startOfDay()->isPast();
    }

    /**
     * Check if there is currently a payment awaiting owner verification.
     */
    public function hasPendingPayment(): bool
    {
        return $this->payments()
            ->where('status', PaymentStatus::MenungguVerifikasi->value)
            ->exists();
    }

    /**
     * Determine if the bill can accept a new payment submission.
     */
    public function canAcceptPayment(): bool
    {
        if ($this->isPaid() || $this->isCancelled()) {
            return false;
        }

        return ! $this->hasPendingPayment();
    }

    /**
     * Calculate remaining outstanding amount to be paid.
     */
    public function outstandingAmount(): float
    {
        return (float) max(0.0, round((float) $this->total_amount - (float) $this->paid_amount, 2));
    }

    /**
     * Format ISO billing period (YYYY-MM) into Indonesian presentation string (e.g. "Oktober 2026").
     */
    public function formattedPeriod(): string
    {
        if (empty($this->billing_period)) {
            return '-';
        }

        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $this->billing_period, $matches)) {
            return Carbon::createFromDate((int) $matches[1], (int) $matches[2], 1)
                ->locale('id')
                ->translatedFormat('F Y');
        }

        return $this->billing_period;
    }

    /**
     * Badge variant string for Soft Neo-Brutalism UI.
     */
    public function statusBadgeVariant(): string
    {
        return $this->statusEnum()?->badgeVariant() ?? 'default';
    }

    /**
     * Badge variant string for BillType.
     */
    public function typeBadgeVariant(): string
    {
        return $this->typeEnum()?->badgeVariant() ?? 'default';
    }

    /**
     * Scope query to recurring rent bills.
     */
    public function scopeRent(Builder $query): Builder
    {
        return $query->where('type', BillType::Sewa->value);
    }

    /**
     * Scope query to security deposit bills.
     */
    public function scopeDeposit(Builder $query): Builder
    {
        return $query->where('type', BillType::Deposit->value);
    }

    /**
     * Scope query to additional charges bills.
     */
    public function scopeAdditional(Builder $query): Builder
    {
        return $query->where('type', BillType::Tambahan->value);
    }

    /**
     * Scope query to unpaid bills.
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', '!=', BillStatus::Lunas->value);
    }
}
