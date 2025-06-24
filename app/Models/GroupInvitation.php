<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'invited_user_id',
        'invited_by_user_id',
        'status',
        'message',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    /**
     * Get the group this invitation is for.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who was invited.
     */
    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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
     * Accept the invitation.
     */
    public function accept(): bool
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->responded_at = now();
        return $this->save();
    }

    /**
     * Decline the invitation.
     */
    public function decline(): bool
    {
        $this->status = self::STATUS_DECLINED;
        $this->responded_at = now();
        return $this->save();
    }

    /**
     * Cancel the invitation.
     */
    public function cancel(): bool
    {
        $this->status = self::STATUS_CANCELLED;
        $this->responded_at = now();
        return $this->save();
    }

    /**
     * Get the formatted status name.
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
