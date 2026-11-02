<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /**
     * Determine whether the user can view any rooms.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can view the room.
     */
    public function view(User $user, Room $room): bool
    {
        if ($user->isOwner()) {
            return (bool) $room->property?->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $room->contracts()->where('user_id', $user->id)->where('status', 'aktif')->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create rooms.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can update the room.
     */
    public function update(User $user, Room $room): bool
    {
        return $user->isOwner() && (bool) $room->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the room.
     */
    public function delete(User $user, Room $room): bool
    {
        return $user->isOwner() && (bool) $room->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can restore the room.
     */
    public function restore(User $user, Room $room): bool
    {
        return $user->isOwner() && (bool) $room->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can permanently delete the room.
     */
    public function forceDelete(User $user, Room $room): bool
    {
        return $user->isOwner() && (bool) $room->property?->isOwnedBy($user);
    }
}
