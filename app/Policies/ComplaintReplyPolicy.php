<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\User;

class ComplaintReplyPolicy
{
    /**
     * Determine whether the user can view the reply.
     */
    public function view(User $user, ComplaintReply $reply): bool
    {
        $complaint = $reply->complaint;

        if (! $complaint) {
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
     * Determine whether the user can create a reply for the specified complaint.
     */
    public function create(User $user, Complaint $complaint): bool
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
     * Operational Protection: Complaint replies are immutable conversation history and must NOT be deleted.
     */
    public function delete(User $user, ComplaintReply $reply): bool
    {
        return false;
    }
}
