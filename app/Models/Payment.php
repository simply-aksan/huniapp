<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payment_number',
        'bill_id',
        'user_id',
        'amount',
        'payment_method',
        'reference_number',
        'payment_date',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
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
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Interact with the payment status, accepting both enum instances and string values.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (string) ($value ?? PaymentStatus::MenungguVerifikasi->value),
            set: fn ($value) => $value instanceof PaymentStatus ? $value->value : (string) $value,
        );
    }

    /**
     * Get the strongly-typed PaymentStatus enum instance.
     */
    public function statusEnum(): ?PaymentStatus
    {
        return PaymentStatus::tryFrom($this->status);
    }

    /**
     * Bill being paid by this transaction.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Tenant user who made this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Owner user who verified this payment.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Payment proof documents or screenshots.
     */
    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    /**
     * Get the property associated with this payment via bill and contract or room.
     */
    public function property(): ?Property
    {
        return $this->bill?->contract?->property ?? $this->bill?->room?->property;
    }

    /**
     * Determine if this payment is awaiting owner verification.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::MenungguVerifikasi->value;
    }

    /**
     * Determine if this payment has been verified successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Berhasil->value;
    }

    /**
     * Determine if this payment has been rejected by the owner.
     */
    public function isRejected(): bool
    {
        return $this->status === PaymentStatus::Ditolak->value;
    }

    /**
     * Badge variant string for Soft Neo-Brutalism UI.
     */
    public function statusBadgeVariant(): string
    {
        return $this->statusEnum()?->badgeVariant() ?? 'default';
    }

    /**
     * Scope query to payments awaiting verification.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::MenungguVerifikasi->value);
    }

    /**
     * Scope query to verified successful payments.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Berhasil->value);
    }
}
