<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    /**
     * Determine whether the user can view any inventory items.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can view the inventory item.
     */
    public function view(User $user, Inventory $inventory): bool
    {
        if ($user->isOwner()) {
            return (bool) $inventory->property?->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            // Tenant can only see items in their active rented room
            if (! $inventory->room_id) {
                return false;
            }

            return $user->contracts()
                ->where('room_id', $inventory->room_id)
                ->where('status', 'aktif')
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create inventory items.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can update the inventory item.
     */
    public function update(User $user, Inventory $inventory): bool
    {
        return $user->isOwner() && (bool) $inventory->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the inventory item.
     */
    public function delete(User $user, Inventory $inventory): bool
    {
        return $user->isOwner() && (bool) $inventory->property?->isOwnedBy($user);
    }
}
