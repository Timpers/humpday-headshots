<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamingSessionParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'gaming_session_id',
        'user_id',
        'status',
        'joined_at',
        'left_at',
        'notes',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    // Status constants
    const STATUS_JOINED = 'joined';
    const STATUS_LEFT = 'left';
    const STATUS_KICKED = 'kicked';

    /**
     * Get the gaming session this participant belongs to.
     */
    public function gamingSession(): BelongsTo
    {
        return $this->belongsTo(GamingSession::class);
    }

    /**
     * Get the user who is participating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Leave the session.
     */
    public function leave(): bool
    {
        return $this->update([
            'status' => self::STATUS_LEFT,
            'left_at' => now(),
        ]);
    }

    /**
     * Kick from the session.
     */
    public function kick(): bool
    {
        return $this->update([
            'status' => self::STATUS_KICKED,
            'left_at' => now(),
        ]);
    }

    /**
     * Check if the participant is active (joined).
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_JOINED;
    }

    /**
     * Scope to get active participants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_JOINED);
    }

    /**
     * Scope to get participants for a specific session.
     */
    public function scopeForSession($query, GamingSession $session)
    {
        return $query->where('gaming_session_id', $session->id);
    }

    /**
     * Scope to get participations for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}
