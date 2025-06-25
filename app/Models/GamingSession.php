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
        return $this->hasMany(GamingSessionParticipant::class);
    }

    /**
     * Get active participants for this session.
     */
    public function activeParticipants(): HasMany
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
     * Get all messages for this session.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(GamingSessionMessage::class)->with('user:id,name')->latest();
    }

    /**
     * Add a participant to this session.
     */
    public function addParticipant(User $user): bool
    {
        // Check if user can join
        if (!$this->canUserJoin($user)) {
            return false;
        }

        // Check if already a participant
        if ($this->isParticipant($user)) {
            return false;
        }

        // Create participant record
        GamingSessionParticipant::create([
            'gaming_session_id' => $this->id,
            'user_id' => $user->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
            'joined_at' => now(),
        ]);

        return true;
    }

    /**
     * Remove a participant from this session.
     */
    public function removeParticipant(User $user): bool
    {
        $participant = GamingSessionParticipant::where([
            'gaming_session_id' => $this->id,
            'user_id' => $user->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ])->first();

        if (!$participant) {
            return false;
        }

        $participant->update([
            'status' => GamingSessionParticipant::STATUS_LEFT,
            'left_at' => now(),
        ]);

        return true;
    }

    /**
     * Check if a user is a participant in this session.
     */
    public function isParticipant(User $user): bool
    {
        return $this->activeParticipants()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user has a pending invitation to this session.
     */
    public function hasPendingInvitation(User $user): bool
    {
        return $this->invitations()
                    ->where('invited_user_id', $user->id)
                    ->where('status', GamingSessionInvitation::STATUS_PENDING)
                    ->exists();
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
        if (!$this->isUpcoming()) {
            return false;
        }

        return $this->scheduled_at <= now()->addHour();
    }

    /**
     * Check if the session is full.
     */
    public function isFull(): bool
    {
        return $this->activeParticipants()->count() >= $this->max_participants;
    }

    /**
     * Check if a user can join this session.
     */
    public function canUserJoin(User $user): bool
    {
        // Cannot join if already a participant
        if ($this->isParticipant($user)) {
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
            // For now, allow anyone to join friends-only sessions
            // TODO: Implement friendship system
            return true;
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
