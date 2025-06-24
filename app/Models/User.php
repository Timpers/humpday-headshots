<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
