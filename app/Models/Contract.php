<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'contract_number',
        'property_id',
        'room_id',
        'user_id',
        'start_date',
        'end_date',
        'duration_months',
        'monthly_rent',
        'total_amount',
        'deposit_amount',
        'deposit_status',
        'payment_method',
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
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_months' => 'integer',
            'monthly_rent' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
        ];
    }

    /**
     * Property associated with this contract.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Room associated with this contract.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Tenant user who signed this contract.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tenant user who signed this contract (semantic alias to user relation).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Contract documents attached to this contract.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ContractDocument::class);
    }

    /**
     * Bills generated under this contract.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Check if contract is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }

    /**
     * Check if contract is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if contract is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'selesai';
    }

    /**
     * Check if contract is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'dibatalkan';
    }

    /**
     * Calculate days remaining until contract ends.
     */
    public function daysRemaining(): int
    {
        if (! $this->end_date) {
            return 0;
        }

        $now = now()->startOfDay();
        $end = $this->end_date->copy()->startOfDay();

        return (int) $now->diffInDays($end, false);
    }

    /**
     * Calculate progress percentage through the contract duration.
     */
    public function progressPercentage(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        if ($totalDays <= 0) {
            return 100;
        }

        $passedDays = $this->start_date->diffInDays(now(), false);
        if ($passedDays <= 0) {
            return 0;
        }

        return (int) min(100, max(0, round(($passedDays / $totalDays) * 100)));
    }

    /**
     * Get badge variant string for Soft Neo-Brutalism badge component.
     */
    public function statusBadgeVariant(): string
    {
        return match ($this->status) {
            'draft' => 'warning',
            'aktif' => 'aktif',
            'selesai' => 'secondary',
            'dibatalkan' => 'danger',
            default => 'default',
        };
    }
}
