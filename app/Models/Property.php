<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'address',
        'city',
        'district',
        'postal_code',
        'description',
        'image_url',
        'is_primary',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Owner user who owns this property.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Check if the given user owns this property.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Get all rooms belonging to this property.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get all inventory items belonging to this property.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Alias for inventory() using plural naming convention.
     */
    public function inventories(): HasMany
    {
        return $this->inventory();
    }

    /**
     * Get facilities associated with this property.
     */
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_property')
            ->withPivot('notes')
            ->withTimestamps();
    }

    /**
     * Get all contracts for rooms in this property.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get all complaints submitted for this property.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * House rules defined for this property.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(PropertyRule::class);
    }

    /**
     * Official FAQ items for this property.
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(PropertyFaq::class);
    }

    /**
     * AI chatbot interaction logs for this property.
     */
    public function chatLogs(): HasMany
    {
        return $this->hasMany(AiChatLog::class);
    }

    /**
     * Retrieve the model for a bound route parameter supporting either numeric ID or slug.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }

        return is_numeric($value)
            ? $this->where('id', (int) $value)->first()
            : $this->where('slug', (string) $value)->first();
    }
}
