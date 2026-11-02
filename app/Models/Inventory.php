<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'room_id',
        'name',
        'serial_number',
        'category',
        'brand',
        'model',
        'location',
        'quantity',
        'condition',
        'purchase_date',
        'purchase_price',
        'warranty_until',
        'last_checked_at',
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
            'quantity' => 'integer',
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
            'warranty_until' => 'date',
            'last_checked_at' => 'date',
        ];
    }

    /**
     * Get the enum representation of item condition.
     */
    public function getConditionEnum(): InventoryCondition
    {
        return InventoryCondition::fromInput($this->condition);
    }

    /**
     * Get user-friendly label for item condition.
     */
    public function getConditionLabelAttribute(): string
    {
        return $this->getConditionEnum()->label();
    }

    /**
     * Get badge variant for item condition.
     */
    public function getConditionBadgeVariantAttribute(): string
    {
        return $this->getConditionEnum()->badgeVariant();
    }

    /**
     * Property owning this inventory item.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Room where this inventory item is placed (if any).
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
