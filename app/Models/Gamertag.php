<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gamertag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'platform',
        'gamertag',
        'display_name',
        'is_public',
        'is_primary',
        'additional_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'is_primary' => 'boolean',
        'additional_data' => 'array',
    ];

    /**
     * Available gaming platforms.
     */
    public const PLATFORMS = [
        'steam' => 'Steam',
        'xbox_live' => 'Xbox Live',
        'playstation_network' => 'PlayStation Network',
        'nintendo_online' => 'Nintendo Online',
        'battlenet' => 'Battle.net',
    ];

    /**
     * Get the user that owns the gamertag.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the platform display name.
     */
    public function getPlatformNameAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? $this->platform;
    }

    /**
     * Scope to get public gamertags only.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get primary gamertags only.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to filter by platform.
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Get the URL for the gaming platform profile (if applicable).
     */
    public function getProfileUrlAttribute(): ?string
    {
        return match ($this->platform) {
            'steam' => "https://steamcommunity.com/id/{$this->gamertag}",
            'xbox_live' => "https://account.xbox.com/en-us/profile?gamertag={$this->gamertag}",
            'playstation_network' => "https://psnprofiles.com/{$this->gamertag}",
            'battlenet' => null, // Battle.net doesn't have public profile URLs
            'nintendo_online' => null, // Nintendo doesn't have public profile URLs
            default => null,
        };
    }
}
