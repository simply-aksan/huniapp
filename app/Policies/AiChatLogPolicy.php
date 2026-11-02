<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AiChatLog;
use App\Models\User;

class AiChatLogPolicy
{
    /**
     * Determine whether the user can view any chat logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isTenant();
    }

    /**
     * Determine whether the user can view the chat log.
     */
    public function view(User $user, AiChatLog $log): bool
    {
        if ($user->isOwner()) {
            return $log->property !== null && $log->property->isOwnedBy($user);
        }

        if ($user->isTenant()) {
            return $log->user_id === $user->id;
        }

        return false;
    }
}
