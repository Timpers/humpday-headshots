<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamingSessionMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'gaming_session_id',
        'user_id',
        'message',
        'type',
        'metadata',
        'edited_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'edited_at' => 'datetime',
    ];

    // Message type constants
    const TYPE_TEXT = 'text';
    const TYPE_SYSTEM = 'system';
    const TYPE_ANNOUNCEMENT = 'announcement';

    /**
     * Get the gaming session this message belongs to.
     */
    public function gamingSession(): BelongsTo
    {
        return $this->belongsTo(GamingSession::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the message has been edited.
     */
    public function isEdited(): bool
    {
        return !is_null($this->edited_at);
    }

    /**
     * Check if the message is a system message.
     */
    public function isSystemMessage(): bool
    {
        return $this->type === self::TYPE_SYSTEM;
    }

    /**
     * Check if the message is an announcement.
     */
    public function isAnnouncement(): bool
    {
        return $this->type === self::TYPE_ANNOUNCEMENT;
    }

    /**
     * Mark the message as edited.
     */
    public function markAsEdited(): void
    {
        $this->update(['edited_at' => now()]);
    }

    /**
     * Scope to get messages for a specific gaming session.
     */
    public function scopeForSession($query, GamingSession $session)
    {
        return $query->where('gaming_session_id', $session->id);
    }

    /**
     * Scope to get recent messages.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope to get messages by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
