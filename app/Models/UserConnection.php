<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'recipient_id', 
        'status',
        'message',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_BLOCKED = 'blocked';

    const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_BLOCKED => 'Blocked',
    ];

    /**
     * Get the user who sent the connection request.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the user who received the connection request.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get accepted connections (friends)
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Get the other user in the connection
     */
    public function getOtherUser($currentUserId)
    {
        return $this->requester_id === $currentUserId ? $this->recipient : $this->requester;
    }

    /**
     * Check if the connection is accepted
     */
    public function isAccepted()
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the connection is pending
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Accept the connection request
     */
    public function accept()
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Decline the connection request
     */
    public function decline()
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
        ]);
    }
}
