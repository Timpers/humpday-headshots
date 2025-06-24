<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\GroupMembershipPivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the gamertags for the user.
     */
    public function gamertags(): HasMany
    {
        return $this->hasMany(Gamertag::class);
    }

    /**
     * Get the games for the user.
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Get public gamertags for the user.
     */
    public function publicGamertags(): HasMany
    {
        return $this->hasMany(Gamertag::class)->where('is_public', true);
    }

    /**
     * Get primary gamertags for the user.
     */
    public function primaryGamertags(): HasMany
    {
        return $this->hasMany(Gamertag::class)->where('is_primary', true);
    }

    /**
     * Get connection requests sent by this user.
     */
    public function sentConnectionRequests(): HasMany
    {
        return $this->hasMany(UserConnection::class, 'requester_id');
    }

    /**
     * Get connection requests received by this user.
     */
    public function receivedConnectionRequests(): HasMany
    {
        return $this->hasMany(UserConnection::class, 'recipient_id');
    }

    /**
     * Get all connections (both sent and received).
     */
    public function connections()
    {
        return UserConnection::where(function ($query) {
            $query->where('requester_id', $this->id)
                  ->orWhere('recipient_id', $this->id);
        });
    }

    /**
     * Get accepted connections (friends).
     */
    public function friends()
    {
        return $this->connections()->accepted();
    }

    /**
     * Get friend users (actual User models).
     */
    public function friendUsers()
    {
        $friendConnections = $this->friends()->get();
        $friendIds = $friendConnections->map(function ($connection) {
            return $connection->requester_id === $this->id 
                ? $connection->recipient_id 
                : $connection->requester_id;
        });

        return User::whereIn('id', $friendIds)->get();
    }

    /**
     * Get pending connection requests received.
     */
    public function pendingReceivedRequests()
    {
        return $this->receivedConnectionRequests()->pending();
    }

    /**
     * Get pending connection requests sent.
     */
    public function pendingSentRequests()
    {
        return $this->sentConnectionRequests()->pending();
    }

    /**
     * Check if this user is connected to another user.
     */
    public function isConnectedTo($userId)
    {
        return $this->connections()
            ->accepted()
            ->where(function ($query) use ($userId) {
                $query->where('requester_id', $userId)
                      ->orWhere('recipient_id', $userId);
            })
            ->exists();
    }

    /**
     * Check if this user has a pending request with another user.
     */
    public function hasPendingRequestWith($userId)
    {
        return $this->connections()
            ->pending()
            ->where(function ($query) use ($userId) {
                $query->where('requester_id', $userId)
                      ->orWhere('recipient_id', $userId);
            })
            ->exists();
    }

    /**
     * Get the connection status with another user.
     */
    public function getConnectionStatusWith($userId)
    {
        $connection = $this->connections()
            ->where(function ($query) use ($userId) {
                $query->where('requester_id', $userId)
                      ->orWhere('recipient_id', $userId);
            })
            ->first();

        if (!$connection) {
            return null;
        }

        return [
            'status' => $connection->status,
            'is_requester' => $connection->requester_id === $this->id,
            'connection' => $connection,
        ];
    }

    // GROUP RELATIONSHIPS

    /**
     * Get groups owned by this user.
     */
    public function ownedGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    /**
     * Get all group memberships for this user.
     */
    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }

    /**
     * Get all groups this user is a member of.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_memberships')
                    ->using(GroupMembershipPivot::class)
                    ->withPivot(['role', 'joined_at', 'permissions'])
                    ->withTimestamps()
                    ->orderBy('group_memberships.joined_at', 'desc');
    }

    /**
     * Get group invitations sent by this user.
     */
    public function sentGroupInvitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class, 'invited_by_user_id');
    }

    /**
     * Get group invitations received by this user.
     */
    public function receivedGroupInvitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class, 'invited_user_id');
    }

    /**
     * Get pending group invitations for this user.
     */
    public function pendingGroupInvitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class, 'invited_user_id')->where('status', 'pending');
    }

    /**
     * Check if user is a member of a specific group.
     */
    public function isMemberOf(Group $group): bool
    {
        return $this->groupMemberships()->where('group_id', $group->id)->exists();
    }

    /**
     * Check if user is an admin of a specific group.
     */
    public function isAdminOf(Group $group): bool
    {
        $membership = $this->groupMemberships()->where('group_id', $group->id)->first();
        return $membership && in_array($membership->role, ['admin', 'owner']);
    }

    /**
     * Check if user owns a specific group.
     */
    public function ownsGroup(Group $group): bool
    {
        return $group->owner_id === $this->id;
    }

    /**
     * Get the user's role in a specific group.
     */
    public function getRoleInGroup(Group $group): ?string
    {
        $membership = $this->groupMemberships()->where('group_id', $group->id)->first();
        return $membership ? $membership->role : null;
    }

    /**
     * Get array of friend user IDs.
     */
    public function getFriendIds(): array
    {
        $friendConnections = $this->friends()->get();
        return $friendConnections->map(function ($connection) {
            return $connection->requester_id === $this->id 
                ? $connection->recipient_id 
                : $connection->requester_id;
        })->toArray();
    }

    /**
     * Get gaming sessions hosted by this user.
     */
    public function hostedGamingSessions(): HasMany
    {
        return $this->hasMany(GamingSession::class, 'host_user_id');
    }

    /**
     * Get gaming sessions this user is participating in.
     */
    public function gamingSessionParticipations(): HasMany
    {
        return $this->hasMany(GamingSessionParticipant::class);
    }

    /**
     * Get gaming sessions this user is participating in (active).
     */
    public function activeGamingSessions(): BelongsToMany
    {
        return $this->belongsToMany(GamingSession::class, 'gaming_session_participants', 'user_id', 'gaming_session_id')
                    ->withPivot(['status', 'joined_at', 'left_at', 'notes'])
                    ->withTimestamps()
                    ->wherePivot('status', GamingSessionParticipant::STATUS_JOINED);
    }

    /**
     * Get gaming session invitations sent by this user.
     */
    public function sentGamingSessionInvitations(): HasMany
    {
        return $this->hasMany(GamingSessionInvitation::class, 'invited_by_user_id');
    }

    /**
     * Get gaming session invitations received by this user.
     */
    public function receivedGamingSessionInvitations(): HasMany
    {
        return $this->hasMany(GamingSessionInvitation::class, 'invited_user_id');
    }

    /**
     * Get pending gaming session invitations for this user.
     */
    public function pendingGamingSessionInvitations(): HasMany
    {
        return $this->receivedGamingSessionInvitations()->where('status', GamingSessionInvitation::STATUS_PENDING);
    }
}
