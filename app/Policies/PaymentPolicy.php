<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isOwner()) {
            $property = $payment->property();

            return $property !== null && $property->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $payment->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create a payment submission.
     */
    public function create(User $user): bool
    {
        return $user->isTenant();
    }

    /**
     * Determine whether the user can verify (approve/reject) the payment.
     */
    public function verify(User $user, Payment $payment): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        $property = $payment->property();

        return $property !== null && $property->isOwnedBy($user);
    }

    /**
     * Determine whether the user can download payment proof files.
     */
    public function downloadProof(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment);
    }

    /**
     * Financial Protection: Payment records must NEVER be hard-deleted.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
