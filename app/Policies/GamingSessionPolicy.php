<?php

namespace App\Policies;

use App\Models\GamingSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GamingSessionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Any authenticated user can view gaming sessions
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GamingSession $gamingSession): bool
    {
        // Users can view public sessions, sessions they're hosting, participating in, or invited to
        if ($gamingSession->privacy === 'public') {
            return true;
        }

        // Host can always view their own session
        if ($gamingSession->host_user_id === $user->id) {
            return true;
        }

        // Check if user is a participant
        if ($gamingSession->participantUsers()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check if user has an invitation
        if ($gamingSession->invitations()->where('invited_user_id', $user->id)->exists()) {
            return true;
        }

        // For friends_only sessions, check if user is a friend of the host
        if ($gamingSession->privacy === 'friends_only') {
            return $gamingSession->host->friendUsers()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create gaming sessions
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GamingSession $gamingSession): bool
    {
        // Only the host can update their gaming session
        return $gamingSession->host_user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GamingSession $gamingSession): bool
    {
        // Only the host can delete (cancel) their gaming session
        return $gamingSession->host_user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GamingSession $gamingSession): bool
    {
        // Only the host can restore their gaming session
        return $gamingSession->host_user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GamingSession $gamingSession): bool
    {
        // Only the host can permanently delete their gaming session
        return $gamingSession->host_user_id === $user->id;
    }
}
