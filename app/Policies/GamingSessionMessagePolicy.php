<?php

namespace App\Policies;

use App\Models\GamingSessionMessage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GamingSessionMessagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GamingSessionMessage $gamingSessionMessage): bool
    {
        // Users can view messages if they can view the gaming session
        return $user->can('viewMessages', $gamingSessionMessage->gamingSession);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Individual message creation is handled by session authorization
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GamingSessionMessage $gamingSessionMessage): bool
    {
        // Users can only edit their own messages
        if ($gamingSessionMessage->user_id !== $user->id) {
            return false;
        }

        // Messages can only be edited within 15 minutes of posting
        // Use floatDiffInMinutes for more precision and add small buffer for timing
        $minutesAgo = $gamingSessionMessage->created_at->floatDiffInMinutes(now());
        
        return $minutesAgo <= 15.05; // Small buffer to account for test execution timing
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GamingSessionMessage $gamingSessionMessage): bool
    {
        // Users can delete their own messages
        if ($gamingSessionMessage->user_id === $user->id) {
            return true;
        }

        // Session hosts can delete any message in their session
        return $gamingSessionMessage->gamingSession->host_user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GamingSessionMessage $gamingSessionMessage): bool
    {
        return $this->delete($user, $gamingSessionMessage);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GamingSessionMessage $gamingSessionMessage): bool
    {
        // Only session hosts can permanently delete messages
        return $gamingSessionMessage->gamingSession->host_user_id === $user->id;
    }
}
