<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'room_number',
        'floor',
        'type',
        'price',
        'size',
        'capacity',
        'status',
        'description',
        'internal_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'price' => 'decimal:2',
            'capacity' => 'integer',
        ];
    }

    /**
     * Get the property that owns this room.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get facilities available in this room.
     */
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_room')
            ->withPivot('notes')
            ->withTimestamps();
    }

    /**
     * Get inventory items assigned to this room.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get invitations sent for this room.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get contracts associated with this room.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get the active contract for this room.
     */
    public function activeContract(): HasOne
    {
        return $this->hasOne(Contract::class)->where('status', 'aktif');
    }

    /**
     * Get bills generated for this room.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Get complaints related to this room.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
