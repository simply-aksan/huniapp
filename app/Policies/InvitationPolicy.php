<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    /**
     * Determine whether the user can view any invitations.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can view the invitation.
     */
    public function view(User $user, Invitation $invitation): bool
    {
        if ($user->isOwner()) {
            return $invitation->invited_by === $user->id;
        }

        if ($user->isTenant()) {
            return $invitation->user_id === $user->id || strcasecmp($invitation->email ?? '', $user->email) === 0;
        }

        return false;
    }

    /**
     * Determine whether the user can create invitations.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can update or revoke the invitation.
     */
    public function update(User $user, Invitation $invitation): bool
    {
        return $user->isOwner() && $invitation->invited_by === $user->id;
    }

    /**
     * Determine whether the user can delete or cancel the invitation.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return $user->isOwner() && $invitation->invited_by === $user->id;
    }
}
