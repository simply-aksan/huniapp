<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ticket_number',
        'property_id',
        'room_id',
        'user_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'photo_url',
        'handled_by',
        'estimated_completion',
        'resolved_at',
        'closed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_completion' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Property where the issue occurred.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Room associated with the complaint.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Tenant user who submitted this complaint.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Follow-up discussions or replies to this ticket ordered chronologically.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ComplaintReply::class)->orderBy('created_at', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Get the strongly-typed ComplaintStatus enum instance.
     */
    public function statusEnum(): ?ComplaintStatus
    {
        return ComplaintStatus::tryFrom((string) $this->status);
    }

    /**
     * Get the strongly-typed ComplaintPriority enum instance.
     */
    public function priorityEnum(): ?ComplaintPriority
    {
        return ComplaintPriority::tryFrom((string) $this->priority);
    }

    /**
     * Get the strongly-typed ComplaintCategory enum instance.
     */
    public function categoryEnum(): ?ComplaintCategory
    {
        return ComplaintCategory::tryFrom((string) $this->category);
    }

    /**
     * Determine if this ticket is still actively open.
     */
    public function isOpen(): bool
    {
        return $this->statusEnum()?->isOpen() ?? in_array($this->status, ['baru', 'diproses', 'menunggu_tanggapan'], true);
    }

    /**
     * Determine if this ticket is currently in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === ComplaintStatus::Diproses->value;
    }

    /**
     * Determine if this ticket is waiting for response.
     */
    public function isPendingResponse(): bool
    {
        return $this->status === ComplaintStatus::MenungguTanggapan->value;
    }

    /**
     * Determine if this ticket has been marked resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === ComplaintStatus::Selesai->value;
    }

    /**
     * Determine if this ticket has been permanently closed.
     */
    public function isClosed(): bool
    {
        return $this->status === ComplaintStatus::Ditutup->value;
    }

    /**
     * Determine if this ticket can still receive new replies.
     */
    public function canBeReplied(): bool
    {
        return ! $this->isClosed();
    }

    /**
     * Determine if ticket is eligible for H+7 auto-close.
     */
    public function isEligibleForAutoClose(): bool
    {
        return $this->isResolved()
            && $this->resolved_at !== null
            && $this->resolved_at->diffInDays(now()) >= 7;
    }

    /**
     * Get label corresponding to current status.
     */
    public function statusLabel(): string
    {
        return $this->statusEnum()?->label() ?? ucfirst((string) $this->status);
    }

    /**
     * Get label corresponding to current priority.
     */
    public function priorityLabel(): string
    {
        return $this->priorityEnum()?->label() ?? ucfirst((string) $this->priority);
    }

    /**
     * Get label corresponding to current category.
     */
    public function categoryLabel(): string
    {
        return $this->categoryEnum()?->label() ?? ucfirst((string) $this->category);
    }

    /**
     * Get badge variant corresponding to current status.
     */
    public function statusBadgeVariant(): string
    {
        return $this->statusEnum()?->badgeVariant() ?? 'secondary';
    }

    /**
     * Get badge variant corresponding to current priority.
     */
    public function priorityBadgeVariant(): string
    {
        return $this->priorityEnum()?->badgeVariant() ?? 'secondary';
    }

    /**
     * Scope query to complaints on properties owned by the given owner.
     */
    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->whereHas('property', fn (Builder $pq) => $pq->where('owner_id', $owner->id));
    }

    /**
     * Scope query to complaints submitted by the given tenant.
     */
    public function scopeForTenant(Builder $query, User $tenant): Builder
    {
        return $query->where('user_id', $tenant->id);
    }

    /**
     * Scope query to open tickets.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ComplaintStatus::Baru->value,
            ComplaintStatus::Diproses->value,
            ComplaintStatus::MenungguTanggapan->value,
        ]);
    }

    /**
     * Scope query to resolved tickets.
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', ComplaintStatus::Selesai->value);
    }

    /**
     * Scope query to closed tickets.
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', ComplaintStatus::Ditutup->value);
    }
}
