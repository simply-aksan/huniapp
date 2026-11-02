<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category',
        'icon',
        'description',
    ];

    /**
     * Properties having this facility.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'facility_property')
            ->withPivot('notes')
            ->withTimestamps();
    }

    /**
     * Rooms having this facility.
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'facility_room')
            ->withPivot('notes')
            ->withTimestamps();
    }
}
