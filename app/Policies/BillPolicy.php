<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;

class BillPolicy
{
    /**
     * Determine whether the user can view any bills.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isTenant();
    }

    /**
     * Determine whether the user can view the bill.
     */
    public function view(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            $property = $bill->contract?->property ?? $bill->room?->property;

            return $property !== null && $property->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $bill->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create bills.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can update the bill.
     */
    public function update(User $user, Bill $bill): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        $property = $bill->contract?->property ?? $bill->room?->property;

        return $property !== null && $property->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the bill.
     * Financial Protection: Bills with recorded payments must NEVER be hard-deleted.
     */
    public function delete(User $user, Bill $bill): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        // Financial integrity: Prohibit hard-deletion if payment history exists
        if ($bill->payments()->exists()) {
            return false;
        }

        $property = $bill->contract?->property ?? $bill->room?->property;

        return $property !== null && $property->isOwnedBy($user);
    }

    /**
     * Determine whether the user can cancel the bill.
     */
    public function cancel(User $user, Bill $bill): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        $property = $bill->contract?->property ?? $bill->room?->property;
        if ($property === null || ! $property->isOwnedBy($user)) {
            return false;
        }

        // Already paid or cancelled bills cannot be cancelled
        if ($bill->isPaid() || $bill->isCancelled()) {
            return false;
        }

        // Invoices with successful payments cannot be cancelled through ordinary action
        if ($bill->payments()->where('status', 'berhasil')->exists()) {
            return false;
        }

        // Pending payments must be resolved first before invoice can be cancelled
        if ($bill->hasPendingPayment()) {
            return false;
        }

        return true;
    }
}
