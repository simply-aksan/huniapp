<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    /**
     * Determine whether the user can view any complaints.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isTenant();
    }

    /**
     * Determine whether the user can view the complaint.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->isOwner()) {
            return (bool) $complaint->property?->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $complaint->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can download or preview the complaint photo attachment.
     */
    public function downloadPhoto(User $user, Complaint $complaint): bool
    {
        return $this->view($user, $complaint);
    }

    /**
     * Determine whether the user can create complaints.
     * Tenants create complaints for their leased room.
     */
    public function create(User $user): bool
    {
        return $user->isTenant();
    }

    /**
     * Determine whether the user can update the complaint.
     */
    public function update(User $user, Complaint $complaint): bool
    {
        if ($user->isOwner()) {
            return (bool) $complaint->property?->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $complaint->user_id === $user->id && ! $complaint->isClosed();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the complaint.
     * Operational Protection: Complaints with existing replies or resolved status must NOT be hard-deleted.
     */
    public function delete(User $user, Complaint $complaint): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        // Prohibit hard-deletion if discussion history exists or if ticket was handled
        if ($complaint->replies()->exists() || $complaint->isResolved() || $complaint->isClosed()) {
            return false;
        }

        return (bool) $complaint->property?->isOwnedBy($user);
    }

    /**
     * Determine whether the user can post a reply to this complaint.
     */
    public function reply(User $user, Complaint $complaint): bool
    {
        if ($complaint->isClosed()) {
            return false;
        }

        if ($user->isOwner()) {
            return (bool) $complaint->property?->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $complaint->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can update status of this complaint.
     */
    public function updateStatus(User $user, Complaint $complaint): bool
    {
        if ($user->isOwner()) {
            return (bool) $complaint->property?->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $complaint->user_id === $user->id;
        }

        return false;
    }
}
