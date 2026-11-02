<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Property;
use App\Models\PropertyRule;
use App\Models\User;

class PropertyRulePolicy
{
    /**
     * Determine whether the user can view any property rules.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isTenant();
    }

    /**
     * Determine whether the user can view the property rule.
     */
    public function view(User $user, PropertyRule $rule): bool
    {
        if ($user->isOwner()) {
            return $rule->property !== null && $rule->property->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            // Tenant can only read active rules for the property they currently reside in
            if (! $rule->is_active) {
                return false;
            }

            return $user->contracts()
                ->where('property_id', $rule->property_id)
                ->where('status', 'aktif')
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create property rules.
     */
    public function create(User $user, ?Property $property = null): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        if ($property !== null) {
            return $property->isOwnedBy($user);
        }

        return true;
    }

    /**
     * Determine whether the user can update the property rule.
     */
    public function update(User $user, PropertyRule $rule): bool
    {
        return $user->isOwner() && $rule->property !== null && $rule->property->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the property rule.
     */
    public function delete(User $user, PropertyRule $rule): bool
    {
        return $user->isOwner() && $rule->property !== null && $rule->property->isOwnedBy($user);
    }
}
