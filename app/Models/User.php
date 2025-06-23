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
     * Get gamertag for a specific platform.
     */
    public function getGamertagForPlatform(string $platform): ?Gamertag
    {
        return $this->gamertags()->where('platform', $platform)->first();
    }

    /**
     * Get primary gamertag for a specific platform.
     */
    public function getPrimaryGamertagForPlatform(string $platform): ?Gamertag
    {
        return $this->gamertags()
            ->where('platform', $platform)
            ->where('is_primary', true)
            ->first();
    }
}
