<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'room_id',
        'invited_by',
        'user_id',
        'name',
        'email',
        'phone',
        'token',
        'message',
        'send_via',
        'status',
        'valid_until',
        'accepted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_until' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Target room for this invitation.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Owner user who sent this invitation.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Alias for sender.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Tenant user who accepted this invitation.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'kadaluarsa' || ($this->valid_until && $this->valid_until->isPast());
    }

    /**
     * Check if invitation is pending and actionable.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['aktif', 'pending'], true) && ! $this->isExpired();
    }

    /**
     * Check if invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return in_array($this->status, ['diterima', 'accepted'], true);
    }

    /**
     * Check if invitation has been cancelled/revoked.
     */
    public function isRevoked(): bool
    {
        return in_array($this->status, ['dibatalkan', 'revoked'], true);
    }

    /**
     * Get public acceptance URL for this invitation.
     */
    public function getInvitationUrlAttribute(): string
    {
        return route('invitations.show', ['token' => $this->token]);
    }

    /**
     * Alias expires_at to valid_until.
     */
    public function getExpiresAtAttribute(): ?Carbon
    {
        return $this->valid_until;
    }
}
