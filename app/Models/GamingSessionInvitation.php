<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamingSessionInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'gaming_session_id',
        'invited_user_id',
        'invited_group_id',
        'invited_by_user_id',
        'status',
        'message',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';

    /**
     * Get the gaming session this invitation belongs to.
     */
    public function gamingSession(): BelongsTo
    {
        return $this->belongsTo(GamingSession::class);
    }

    /**
     * Get the user who was invited.
     */
    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    /**
     * Get the group that was invited.
     */
    public function invitedGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'invited_group_id');
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Check if this invitation is for a user.
     */
    public function isUserInvitation(): bool
    {
        return !is_null($this->invited_user_id);
    }

    /**
     * Check if this invitation is for a group.
     */
    public function isGroupInvitation(): bool
    {
        return !is_null($this->invited_group_id);
    }

    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the invitation is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the invitation is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Accept this invitation.
     */
    public function accept(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        // If this is a user invitation, add them to participants
        if ($this->invited_user_id) {
            GamingSessionParticipant::create([
                'gaming_session_id' => $this->gaming_session_id,
                'user_id' => $this->invited_user_id,
                'joined_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Decline this invitation.
     */
    public function decline(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_DECLINED,
            'responded_at' => now(),
        ]);
    }

    /**
     * Scope to get pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get accepted invitations.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope to get declined invitations.
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', self::STATUS_DECLINED);
    }

    /**
     * Scope to get user invitations.
     */
    public function scopeUserInvitations($query)
    {
        return $query->whereNotNull('invited_user_id');
    }

    /**
     * Scope to get group invitations.
     */
    public function scopeGroupInvitations($query)
    {
        return $query->whereNotNull('invited_group_id');
    }

    /**
     * Scope to get invitations for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('invited_user_id', $user->id);
    }
}
