<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class GamingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'host_user_id',
        'title',
        'description',
        'game_name',
        'game_data',
        'platform',
        'scheduled_at',
        'max_participants',
        'status',
        'privacy',
        'requirements',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'game_data' => 'array',
    ];

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Privacy constants
    const PRIVACY_PUBLIC = 'public';
    const PRIVACY_FRIENDS_ONLY = 'friends_only';
    const PRIVACY_INVITE_ONLY = 'invite_only';

    /**
     * Get the user who hosts this session.
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    /**
     * Get all invitations for this session.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(GamingSessionInvitation::class);
    }

    /**
     * Get pending invitations for this session.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->where('status', GamingSessionInvitation::STATUS_PENDING);
    }

    /**
     * Get accepted invitations for this session.
     */
    public function acceptedInvitations(): HasMany
    {
        return $this->invitations()->where('status', GamingSessionInvitation::STATUS_ACCEPTED);
    }

    /**
     * Get all participants for this session.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(GamingSessionParticipant::class)->where('status', GamingSessionParticipant::STATUS_JOINED);
    }

    /**
     * Get all users who are participants in this session.
     */
    public function participantUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'gaming_session_participants', 'gaming_session_id', 'user_id')
                    ->withPivot(['status', 'joined_at', 'left_at', 'notes'])
                    ->withTimestamps()
                    ->wherePivot('status', GamingSessionParticipant::STATUS_JOINED);
    }

    /**
     * Check if the session is in the past.
     */
    public function isPast(): bool
    {
        return $this->scheduled_at->isPast();
    }

    /**
     * Check if the session is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->scheduled_at->isFuture();
    }

    /**
     * Check if the session is happening soon (within 1 hour).
     */
    public function isSoon(): bool
    {
        return $this->scheduled_at->diffInMinutes(now()) <= 60 && $this->isUpcoming();
    }

    /**
     * Check if the session is full.
     */
    public function isFull(): bool
    {
        return $this->participants()->count() >= $this->max_participants;
    }

    /**
     * Check if a user can join this session.
     */
    public function canUserJoin(User $user): bool
    {
        // Cannot join if already a participant
        if ($this->participantUsers()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Cannot join if session is full
        if ($this->isFull()) {
            return false;
        }

        // Cannot join if session is in the past or cancelled
        if ($this->isPast() || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        // Host can always join their own session
        if ($this->host_user_id === $user->id) {
            return true;
        }

        // Check privacy settings
        if ($this->privacy === self::PRIVACY_PUBLIC) {
            return true;
        }

        if ($this->privacy === self::PRIVACY_FRIENDS_ONLY) {
            return $this->host->friendUsers()->contains($user->id);
        }

        if ($this->privacy === self::PRIVACY_INVITE_ONLY) {
            return $this->invitations()
                        ->where('invited_user_id', $user->id)
                        ->where('status', GamingSessionInvitation::STATUS_ACCEPTED)
                        ->exists();
        }

        return false;
    }

    /**
     * Get the game cover image URL.
     */
    public function getGameCoverUrlAttribute(): ?string
    {
        if ($this->game_data && isset($this->game_data['cover']['url'])) {
            return 'https:' . $this->game_data['cover']['url'];
        }
        return null;
    }

    /**
     * Scope to get sessions for a specific user (hosting or participating).
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('host_user_id', $user->id)
              ->orWhereHas('participantUsers', function ($pq) use ($user) {
                  $pq->where('user_id', $user->id);
              });
        });
    }

    /**
     * Scope to get upcoming sessions.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_ACTIVE]);
    }

    /**
     * Scope to get public sessions.
     */
    public function scopePublic($query)
    {
        return $query->where('privacy', self::PRIVACY_PUBLIC);
    }
}
