<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'property_id',
        'session_id',
        'question',
        'answer',
        'sources',
        'is_fallback',
        'relevance_score',
        'processing_time_ms',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'is_fallback' => 'boolean',
            'relevance_score' => 'decimal:3',
            'processing_time_ms' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * User who initiated the conversation (null if guest or account removed).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Property context associated with the query.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Scope query to logs for a specific property.
     */
    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope query to logs for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query to fallback answers (e.g. out of domain / low confidence).
     */
    public function scopeFallback(Builder $query): Builder
    {
        return $query->where('is_fallback', true);
    }
}
