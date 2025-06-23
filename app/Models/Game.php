<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'igdb_id',
        'name',
        'summary',
        'slug',
        'cover',
        'screenshots',
        'release_date',
        'genres',
        'platforms',
        'rating',
        'status',
        'platform',
        'user_rating',
        'notes',
        'hours_played',
        'date_purchased',
        'price_paid',
        'is_digital',
        'is_completed',
        'is_favorite',
    ];

    protected $casts = [
        'cover' => 'array',
        'screenshots' => 'array',
        'genres' => 'array',
        'platforms' => 'array',
        'release_date' => 'date',
        'date_purchased' => 'date',
        'rating' => 'decimal:1',
        'user_rating' => 'decimal:1',
        'price_paid' => 'decimal:2',
        'is_digital' => 'boolean',
        'is_completed' => 'boolean',
        'is_favorite' => 'boolean',
    ];

    // Status constants
    const STATUS_OWNED = 'owned';
    const STATUS_WISHLIST = 'wishlist';
    const STATUS_PLAYING = 'playing';
    const STATUS_COMPLETED = 'completed';

    const STATUSES = [
        self::STATUS_OWNED => 'Owned',
        self::STATUS_WISHLIST => 'Wishlist',
        self::STATUS_PLAYING => 'Currently Playing',
        self::STATUS_COMPLETED => 'Completed',
    ];

    // Gaming platforms
    const PLATFORMS = [
        'pc' => 'PC',
        'playstation_5' => 'PlayStation 5',
        'playstation_4' => 'PlayStation 4',
        'xbox_series' => 'Xbox Series X/S',
        'xbox_one' => 'Xbox One',
        'nintendo_switch' => 'Nintendo Switch',
        'steam' => 'Steam',
        'epic_games' => 'Epic Games Store',
        'mobile' => 'Mobile',
        'other' => 'Other',
    ];

    /**
     * Get the user that owns the game.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by platform
     */
    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter favorites
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope to filter completed games
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Get the formatted status
     */
    public function getFormattedStatusAttribute()
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the formatted platform
     */
    public function getFormattedPlatformAttribute()
    {
        return self::PLATFORMS[$this->platform] ?? ucfirst($this->platform);
    }

    /**
     * Get cover image URL
     */
    public function getCoverUrlAttribute()
    {
        if ($this->cover && isset($this->cover['url'])) {
            // IGDB images need to be formatted properly
            return 'https:' . str_replace('t_thumb', 't_cover_big', $this->cover['url']);
        }
        return null;
    }

    /**
     * Get thumbnail cover URL
     */
    public function getCoverThumbnailAttribute()
    {
        if ($this->cover && isset($this->cover['url'])) {
            return 'https:' . $this->cover['url'];
        }
        return null;
    }

    /**
     * Get formatted genres
     */
    public function getFormattedGenresAttribute()
    {
        if ($this->genres && is_array($this->genres)) {
            return collect($this->genres)->pluck('name')->join(', ');
        }
        return '';
    }
}
