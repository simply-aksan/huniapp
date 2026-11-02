<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'nik',
        'occupation',
        'role',
        'avatar_url',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Properties owned by this user (if owner).
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    /**
     * Invitations sent by this user (if owner).
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    /**
     * Invitations accepted by this user (tenant).
     */
    public function acceptedInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'user_id');
    }

    /**
     * Contracts where this user is the tenant.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'user_id');
    }

    /**
     * Bills issued to this user.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'user_id');
    }

    /**
     * Payments made by this user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    /**
     * Payments verified by this user (owner).
     */
    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }

    /**
     * Complaints submitted by this user.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'user_id');
    }

    /**
     * Complaint replies sent by this user.
     */
    public function complaintReplies(): HasMany
    {
        return $this->hasMany(ComplaintReply::class, 'user_id');
    }

    /**
     * AI chatbot logs initiated by this user.
     */
    public function chatLogs(): HasMany
    {
        return $this->hasMany(AiChatLog::class, 'user_id');
    }

    /**
     * Check if user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    /**
     * Check if user is a tenant.
     */
    public function isTenant(): bool
    {
        return $this->role === UserRole::Tenant;
    }

    /**
     * Check if this tenant is associated with a specific owner.
     */
    public function isTenantOf(User $owner): bool
    {
        if (! $this->isTenant() || ! $owner->isOwner()) {
            return false;
        }

        $hasContract = $this->contracts()
            ->whereHas('property', fn ($q) => $q->where('owner_id', $owner->id))
            ->exists();

        if ($hasContract) {
            return true;
        }

        return $this->acceptedInvitations()
            ->where('invited_by', $owner->id)
            ->whereIn('status', ['diterima', 'accepted'])
            ->exists();
    }
}
