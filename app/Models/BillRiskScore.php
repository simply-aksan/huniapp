<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DelinquencyRiskTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillRiskScore extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bill_id',
        'risk_score',
        'risk_tier',
        'evaluator_type',
        'model_version',
        'contributing_factors',
        'metadata',
        'evaluated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'risk_score' => 'decimal:2',
            'risk_tier' => DelinquencyRiskTier::class,
            'contributing_factors' => 'array',
            'metadata' => 'array',
            'evaluated_at' => 'datetime',
        ];
    }

    /**
     * Strongly-typed risk tier enum instance.
     */
    public function riskTierEnum(): DelinquencyRiskTier
    {
        if ($this->risk_tier instanceof DelinquencyRiskTier) {
            return $this->risk_tier;
        }

        return DelinquencyRiskTier::tryFrom((string) $this->risk_tier) ?? DelinquencyRiskTier::Rendah;
    }

    /**
     * Bill evaluated by this risk score.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Badge variant string for Soft Neo-Brutalism UI.
     */
    public function badgeVariant(): string
    {
        return $this->riskTierEnum()?->badgeVariant() ?? 'default';
    }

    /**
     * Human-readable tier label.
     */
    public function tierLabel(): string
    {
        return $this->riskTierEnum()?->label() ?? ucfirst((string) $this->risk_tier);
    }

    /**
     * Check if evaluation classified as high risk.
     */
    public function isHighRisk(): bool
    {
        return $this->riskTierEnum() === DelinquencyRiskTier::Tinggi;
    }

    /**
     * Check if evaluation classified as medium risk.
     */
    public function isMediumRisk(): bool
    {
        return $this->riskTierEnum() === DelinquencyRiskTier::Sedang;
    }

    /**
     * Check if evaluation classified as low risk.
     */
    public function isLowRisk(): bool
    {
        return $this->riskTierEnum() === DelinquencyRiskTier::Rendah;
    }

    /**
     * Scope query to high risk scores.
     */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->where('risk_tier', DelinquencyRiskTier::Tinggi->value);
    }

    /**
     * Scope query to medium risk scores.
     */
    public function scopeMediumRisk(Builder $query): Builder
    {
        return $query->where('risk_tier', DelinquencyRiskTier::Sedang->value);
    }

    /**
     * Scope query to low risk scores.
     */
    public function scopeLowRisk(Builder $query): Builder
    {
        return $query->where('risk_tier', DelinquencyRiskTier::Rendah->value);
    }
}
