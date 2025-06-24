<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'game',
        'platform',
        'owner_id',
        'is_public',
        'max_members',
        'avatar',
        'settings',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'settings' => 'array',
    ];

    // Available platforms (same as gamertags)
    const PLATFORMS = [
        'steam' => 'Steam',
        'xbox_live' => 'Xbox Live',
        'playstation_network' => 'PlayStation Network',
        'nintendo_online' => 'Nintendo Online',
        'battlenet' => 'Battle.net',
        'cross_platform' => 'Cross Platform',
    ];

    // Popular games
    const POPULAR_GAMES = [
        'call_of_duty' => 'Call of Duty',
        'halo' => 'Halo',
        'fifa' => 'FIFA',
        'fortnite' => 'Fortnite',
        'apex_legends' => 'Apex Legends',
        'valorant' => 'Valorant',
        'counter_strike' => 'Counter-Strike',
        'overwatch' => 'Overwatch',
        'rocket_league' => 'Rocket League',
        'minecraft' => 'Minecraft',
        'gta_v' => 'Grand Theft Auto V',
        'destiny' => 'Destiny',
        'warframe' => 'Warframe',
        'league_of_legends' => 'League of Legends',
        'dota_2' => 'Dota 2',
        'other' => 'Other',
    ];

    /**
     * Get the owner of the group.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all memberships for this group.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }

    /**
     * Get all members of this group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_memberships')
                    ->withPivot(['role', 'joined_at', 'permissions'])
                    ->withTimestamps()
                    ->orderBy('group_memberships.joined_at');
    }

    /**
     * Get all invitations for this group.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class);
    }

    /**
     * Get pending invitations for this group.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class)->where('status', 'pending');
    }

    /**
     * Scope to filter by public groups.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter by game.
     */
    public function scopeByGame($query, $game)
    {
        return $query->where('game', $game);
    }

    /**
     * Scope to filter by platform.
     */
    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Get the formatted platform name.
     */
    public function getPlatformNameAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? $this->platform;
    }

    /**
     * Get the formatted game name.
     */
    public function getGameNameAttribute(): string
    {
        return self::POPULAR_GAMES[$this->game] ?? $this->game;
    }

    /**
     * Get the current member count.
     */
    public function getMemberCountAttribute(): int
    {
        return $this->memberships()->count();
    }

    /**
     * Check if the group is full.
     */
    public function isFull(): bool
    {
        return $this->member_count >= $this->max_members;
    }

    /**
     * Check if a user is a member of this group.
     */
    public function hasMember(User $user): bool
    {
        return $this->memberships()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is the owner of this group.
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Check if a user is an admin or owner of this group.
     */
    public function isAdmin(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        $membership = $this->memberships()->where('user_id', $user->id)->first();
        return $membership && in_array($membership->role, ['admin', 'owner']);
    }

    /**
     * Check if a user can invite others to this group.
     */
    public function canInvite(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        $membership = $this->memberships()->where('user_id', $user->id)->first();
        return $membership && in_array($membership->role, ['admin', 'moderator', 'owner']);
    }

    /**
     * Get the user's membership in this group.
     */
    public function getMembershipFor(User $user): ?GroupMembership
    {
        return $this->memberships()->where('user_id', $user->id)->first();
    }

    /**
     * Check if a user has a pending invitation to this group.
     */
    public function hasPendingInvitation(User $user): bool
    {
        return $this->invitations()
                    ->where('invited_user_id', $user->id)
                    ->where('status', 'pending')
                    ->exists();
    }
}
