<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'joined_at',
        'permissions',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'permissions' => 'array',
    ];

    // Role constants
    const ROLE_MEMBER = 'member';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_ADMIN = 'admin';
    const ROLE_OWNER = 'owner';

    const ROLES = [
        self::ROLE_MEMBER => 'Member',
        self::ROLE_MODERATOR => 'Moderator',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_OWNER => 'Owner',
    ];

    /**
     * Get the group this membership belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user this membership belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by role.
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get admin members (admin and owner).
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [self::ROLE_ADMIN, self::ROLE_OWNER]);
    }

    /**
     * Check if this membership is an admin role.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_OWNER]);
    }

    /**
     * Check if this membership is the owner.
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Check if this membership can invite others.
     */
    public function canInvite(): bool
    {
        return in_array($this->role, [self::ROLE_MODERATOR, self::ROLE_ADMIN, self::ROLE_OWNER]);
    }

    /**
     * Get the formatted role name.
     */
    public function getRoleNameAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
