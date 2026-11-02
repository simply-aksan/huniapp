<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    /**
     * Determine whether the user can view any contracts.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can view the contract.
     * Accessible by the owning property Owner OR the assigned Tenant.
     */
    public function view(User $user, Contract $contract): bool
    {
        if ($user->isOwner()) {
            return (bool) $contract->property?->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $contract->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create contracts.
     * Only owners can draft and create rental contracts.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can update the contract.
     * Only the property owner can update contract details.
     */
    public function update(User $user, Contract $contract): bool
    {
        return $user->isOwner() && (bool) $contract->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the contract.
     * Only the property owner can delete a contract, and only if no financial records exist.
     */
    public function delete(User $user, Contract $contract): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        // Financial integrity: Prohibit hard-deletion if invoices/bills exist
        if ($contract->bills()->exists()) {
            return false;
        }

        return (bool) $contract->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can restore the contract.
     */
    public function restore(User $user, Contract $contract): bool
    {
        return $user->isOwner() && (bool) $contract->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can permanently delete the contract.
     */
    public function forceDelete(User $user, Contract $contract): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        // Financial integrity: Prohibit permanent deletion if invoices/bills exist
        if ($contract->bills()->exists()) {
            return false;
        }

        return (bool) $contract->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can upload a document to the contract.
     */
    public function uploadDocument(User $user, Contract $contract): bool
    {
        return $user->isOwner() && (bool) $contract->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can download a contract document.
     */
    public function downloadDocument(User $user, Contract $contract): bool
    {
        return $this->view($user, $contract);
    }

    /**
     * Determine whether the user can delete a contract document.
     * Documents belonging to completed or cancelled contracts must be permanently retained.
     */
    public function deleteDocument(User $user, Contract $contract): bool
    {
        if ($contract->isCompleted() || $contract->isCancelled()) {
            return false;
        }

        return $user->isOwner() && (bool) $contract->property?->isOwnedBy($user);
    }
}
