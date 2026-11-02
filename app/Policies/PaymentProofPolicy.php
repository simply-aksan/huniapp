<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PaymentProof;
use App\Models\User;

class PaymentProofPolicy
{
    /**
     * Determine whether the user can view the payment proof.
     */
    public function view(User $user, PaymentProof $proof): bool
    {
        $payment = $proof->payment;

        if (! $payment) {
            return false;
        }

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
     * Determine whether the user can download the payment proof.
     */
    public function download(User $user, PaymentProof $proof): bool
    {
        return $this->view($user, $proof);
    }
}
