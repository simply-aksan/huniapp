<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BillRiskScore;
use App\Models\User;

class BillRiskScorePolicy
{
    /**
     * Determine whether the user can view any bill risk scores.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can view the bill risk score.
     */
    public function view(User $user, BillRiskScore $score): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        $property = $score->bill?->contract?->property ?? $score->bill?->room?->property;

        return $property !== null && $property->isOwnedBy($user);
    }

    /**
     * Determine whether the user can create bill risk scores.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can update the bill risk score.
     */
    public function update(User $user, BillRiskScore $score): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        $property = $score->bill?->contract?->property ?? $score->bill?->room?->property;

        return $property !== null && $property->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the bill risk score.
     */
    public function delete(User $user, BillRiskScore $score): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        $property = $score->bill?->contract?->property ?? $score->bill?->room?->property;

        return $property !== null && $property->isOwnedBy($user);
    }
}
