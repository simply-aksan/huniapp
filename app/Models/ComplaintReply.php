<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintReply extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'complaint_id',
        'user_id',
        'message',
    ];

    /**
     * The complaint ticket being replied to.
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * User who authored this reply.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if this reply was authored by the property owner.
     */
    public function isFromOwner(): bool
    {
        return $this->user?->isOwner() ?? false;
    }

    /**
     * Determine if this reply was authored by the tenant.
     */
    public function isFromTenant(): bool
    {
        return $this->user?->isTenant() ?? false;
    }
}
