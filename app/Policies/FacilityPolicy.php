<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Facility;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;

class FacilityPolicy
{
    /**
     * Determine whether the user can view any facilities.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can view the facility.
     */
    public function view(User $user, Facility $facility): bool
    {
        return $user->isOwner() || $user->isTenant();
    }

    /**
     * Determine whether the user can create facilities.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can update the facility.
     * Forbids updating if the facility is actively in use by properties or rooms of another owner.
     */
    public function update(User $user, Facility $facility): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        $isUsedByOtherOwners = $facility->properties()
            ->where('owner_id', '!=', $user->id)
            ->exists()
            || $facility->rooms()
                ->whereHas('property', fn ($q) => $q->where('owner_id', '!=', $user->id))
                ->exists();

        return ! $isUsedByOtherOwners;
    }

    /**
     * Determine whether the user can delete the facility.
     * Strictly forbids deleting if the facility is attached to properties or rooms of another owner.
     */
    public function delete(User $user, Facility $facility): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        $isUsedByOtherOwners = $facility->properties()
            ->where('owner_id', '!=', $user->id)
            ->exists()
            || $facility->rooms()
                ->whereHas('property', fn ($q) => $q->where('owner_id', '!=', $user->id))
                ->exists();

        return ! $isUsedByOtherOwners;
    }

    /**
     * Determine whether the owner can manage facilities for a property.
     */
    public function manageProperty(User $user, Property $property): bool
    {
        return $user->isOwner() && $property->isOwnedBy($user);
    }

    /**
     * Determine whether the owner can manage facilities for a room.
     */
    public function manageRoom(User $user, Room $room): bool
    {
        return $user->isOwner() && (bool) $room->property?->isOwnedBy($user);
    }
}
