<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Property;
use App\Models\PropertyFaq;
use App\Models\User;

class PropertyFaqPolicy
{
    /**
     * Determine whether the user can view any property FAQs.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isTenant();
    }

    /**
     * Determine whether the user can view the property FAQ.
     */
    public function view(User $user, PropertyFaq $faq): bool
    {
        if ($user->isOwner()) {
            return $faq->property !== null && $faq->property->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            // Tenant can only read active FAQs for the property they currently reside in
            if (! $faq->is_active) {
                return false;
            }

            return $user->contracts()
                ->where('property_id', $faq->property_id)
                ->where('status', 'aktif')
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create property FAQs.
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
     * Determine whether the user can update the property FAQ.
     */
    public function update(User $user, PropertyFaq $faq): bool
    {
        return $user->isOwner() && $faq->property !== null && $faq->property->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the property FAQ.
     */
    public function delete(User $user, PropertyFaq $faq): bool
    {
        return $user->isOwner() && $faq->property !== null && $faq->property->isOwnedBy($user);
    }
}
