<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GameCompatibilityController extends Controller
{
    /**
     * Show the game compatibility comparison page
     */
    public function index(): View
    {
        $user = Auth::user();
        $users = User::where('id', '!=', $user->id)
            ->with(['games' => function($query) {
                $query->where('status', Game::STATUS_OWNED);
            }])
            ->paginate(12);

        return view('games.compatibility.index', compact('users'));
    }

    /**
     * Compare games between current user and another user
     */
    public function compare(User $user)
    {
        $currentUser = Auth::user();
        
        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'You cannot compare with yourself.');
        }

        $comparison = $this->calculateCompatibility($currentUser, $user);

        return view('games.compatibility.compare', compact('user', 'comparison'));
    }

    /**
     * Get compatibility data via AJAX
     */
    public function getCompatibility(User $user)
    {
        $currentUser = Auth::user();
        
        if ($currentUser->id === $user->id) {
            return response()->json(['error' => 'Cannot compare with yourself'], 400);
        }

        $comparison = $this->calculateCompatibility($currentUser, $user);

        return response()->json($comparison);
    }

    /**
     * Calculate compatibility between two users based on their game libraries
     */
    private function calculateCompatibility(User $user1, User $user2): array
    {
        // Get owned games for both users
        $user1Games = $user1->games()
            ->where('status', Game::STATUS_OWNED)
            ->get();
            
        $user2Games = $user2->games()
            ->where('status', Game::STATUS_OWNED)
            ->get();

        // If either user has no games, compatibility is 0
        if ($user1Games->isEmpty() || $user2Games->isEmpty()) {
            return [
                'compatibility_score' => 0,
                'compatibility_rating' => 'No Data',
                'shared_games' => [],
                'user1_only_games' => $user1Games->take(10),
                'user2_only_games' => $user2Games->take(10),
                'total_user1_games' => $user1Games->count(),
                'total_user2_games' => $user2Games->count(),
                'platform_compatibility' => [],
                'genre_compatibility' => [],
                'recommendations' => []
            ];
        }

        // Find shared games (by IGDB ID or name)
        $sharedGames = $this->findSharedGames($user1Games, $user2Games);
        
        // Calculate base compatibility score
        $totalGames = $user1Games->count() + $user2Games->count();
        $sharedCount = $sharedGames->count();
        $baseScore = $totalGames > 0 ? ($sharedCount * 2) / $totalGames * 100 : 0;

        // Platform compatibility boost
        $platformBoost = $this->calculatePlatformCompatibility($user1Games, $user2Games);
        
        // Genre compatibility boost
        $genreBoost = $this->calculateGenreCompatibility($user1Games, $user2Games);
        
        // Final score with boosts
        $finalScore = min(100, $baseScore + $platformBoost + $genreBoost);

        // Get games unique to each user
        $user1OnlyGames = $this->getUniqueGames($user1Games, $user2Games);
        $user2OnlyGames = $this->getUniqueGames($user2Games, $user1Games);

        return [
            'compatibility_score' => round($finalScore, 1),
            'compatibility_rating' => $this->getCompatibilityRating($finalScore),
            'shared_games' => $sharedGames,
            'user1_only_games' => $user1OnlyGames->take(10),
            'user2_only_games' => $user2OnlyGames->take(10),
            'total_user1_games' => $user1Games->count(),
            'total_user2_games' => $user2Games->count(),
            'total_shared_games' => $sharedCount,
            'platform_compatibility' => $this->getPlatformBreakdown($user1Games, $user2Games),
            'genre_compatibility' => $this->getGenreBreakdown($user1Games, $user2Games),
            'recommendations' => $this->getRecommendations($user1OnlyGames, $user2OnlyGames)
        ];
    }

    /**
     * Find games that both users own
     */
    private function findSharedGames($user1Games, $user2Games)
    {
        $user2GameIds = $user2Games->pluck('igdb_id')->filter()->toArray();
        $user2GameNames = $user2Games->pluck('name')->map('strtolower')->toArray();

        return $user1Games->filter(function ($game) use ($user2GameIds, $user2GameNames) {
            // Match by IGDB ID first (more accurate)
            if ($game->igdb_id && in_array($game->igdb_id, $user2GameIds)) {
                return true;
            }
            
            // Fallback to name matching
            return in_array(strtolower($game->name), $user2GameNames);
        });
    }

    /**
     * Get games unique to one user
     */
    private function getUniqueGames($userGames, $otherUserGames)
    {
        $otherGameIds = $otherUserGames->pluck('igdb_id')->filter()->toArray();
        $otherGameNames = $otherUserGames->pluck('name')->map('strtolower')->toArray();

        return $userGames->reject(function ($game) use ($otherGameIds, $otherGameNames) {
            if ($game->igdb_id && in_array($game->igdb_id, $otherGameIds)) {
                return true;
            }
            return in_array(strtolower($game->name), $otherGameNames);
        });
    }

    /**
     * Calculate platform compatibility bonus
     */
    private function calculatePlatformCompatibility($user1Games, $user2Games): float
    {
        $user1Platforms = $user1Games->pluck('platform')->unique()->toArray();
        $user2Platforms = $user2Games->pluck('platform')->unique()->toArray();
        
        $commonPlatforms = array_intersect($user1Platforms, $user2Platforms);
        $totalPlatforms = array_unique(array_merge($user1Platforms, $user2Platforms));
        
        if (empty($totalPlatforms)) {
            return 0;
        }
        
        return (count($commonPlatforms) / count($totalPlatforms)) * 10; // Max 10% boost
    }

    /**
     * Calculate genre compatibility bonus
     */
    private function calculateGenreCompatibility($user1Games, $user2Games): float
    {
        // Extract all genres from both users' games
        $user1Genres = collect();
        $user2Genres = collect();
        
        foreach ($user1Games as $game) {
            if ($game->genres && is_array($game->genres)) {
                foreach ($game->genres as $genre) {
                    $user1Genres->push(is_array($genre) ? ($genre['name'] ?? '') : $genre);
                }
            }
        }
        
        foreach ($user2Games as $game) {
            if ($game->genres && is_array($game->genres)) {
                foreach ($game->genres as $genre) {
                    $user2Genres->push(is_array($genre) ? ($genre['name'] ?? '') : $genre);
                }
            }
        }
        
        $user1GenresUnique = $user1Genres->filter()->unique();
        $user2GenresUnique = $user2Genres->filter()->unique();
        
        if ($user1GenresUnique->isEmpty() || $user2GenresUnique->isEmpty()) {
            return 0;
        }
        
        $commonGenres = $user1GenresUnique->intersect($user2GenresUnique);
        $totalGenres = $user1GenresUnique->merge($user2GenresUnique)->unique();
        
        return ($commonGenres->count() / $totalGenres->count()) * 15; // Max 15% boost
    }

    /**
     * Get platform breakdown for detailed view
     */
    private function getPlatformBreakdown($user1Games, $user2Games): array
    {
        $user1Platforms = $user1Games->groupBy('platform');
        $user2Platforms = $user2Games->groupBy('platform');
        $allPlatforms = collect(Game::PLATFORMS);
        
        $breakdown = [];
        
        foreach ($allPlatforms as $key => $name) {
            $user1Count = $user1Platforms->get($key, collect())->count();
            $user2Count = $user2Platforms->get($key, collect())->count();
            
            if ($user1Count > 0 || $user2Count > 0) {
                $breakdown[] = [
                    'platform' => $name,
                    'user1_count' => $user1Count,
                    'user2_count' => $user2Count,
                    'shared' => $user1Count > 0 && $user2Count > 0
                ];
            }
        }
        
        return $breakdown;
    }

    /**
     * Get genre breakdown for detailed view
     */
    private function getGenreBreakdown($user1Games, $user2Games): array
    {
        $user1Genres = collect();
        $user2Genres = collect();
        
        // Extract and count genres
        foreach ($user1Games as $game) {
            if ($game->genres && is_array($game->genres)) {
                foreach ($game->genres as $genre) {
                    $genreName = is_array($genre) ? ($genre['name'] ?? '') : $genre;
                    if ($genreName) {
                        $user1Genres->push($genreName);
                    }
                }
            }
        }
        
        foreach ($user2Games as $game) {
            if ($game->genres && is_array($game->genres)) {
                foreach ($game->genres as $genre) {
                    $genreName = is_array($genre) ? ($genre['name'] ?? '') : $genre;
                    if ($genreName) {
                        $user2Genres->push($genreName);
                    }
                }
            }
        }
        
        $user1GenreCounts = $user1Genres->countBy();
        $user2GenreCounts = $user2Genres->countBy();
        $allGenres = $user1GenreCounts->keys()->merge($user2GenreCounts->keys())->unique();
        
        return $allGenres->map(function ($genre) use ($user1GenreCounts, $user2GenreCounts) {
            $user1Count = $user1GenreCounts->get($genre, 0);
            $user2Count = $user2GenreCounts->get($genre, 0);
            
            return [
                'genre' => $genre,
                'user1_count' => $user1Count,
                'user2_count' => $user2Count,
                'shared' => $user1Count > 0 && $user2Count > 0
            ];
        })->sortByDesc(function ($item) {
            return $item['user1_count'] + $item['user2_count'];
        })->take(10)->values()->toArray();
    }

    /**
     * Get game recommendations based on what the other user has
     */
    private function getRecommendations($user1OnlyGames, $user2OnlyGames): array
    {
        // Recommend highly-rated games from the other user
        $recommendations = $user2OnlyGames
            ->filter(function ($game) {
                return $game->user_rating >= 7 || $game->is_favorite;
            })
            ->sortByDesc(function ($game) {
                return ($game->user_rating ?? 0) + ($game->is_favorite ? 2 : 0);
            })
            ->take(5)
            ->values();

        return $recommendations->map(function ($game) {
            return [
                'id' => $game->id,
                'name' => $game->name,
                'platform' => $game->platform,
                'platform_name' => Game::PLATFORMS[$game->platform] ?? $game->platform,
                'user_rating' => $game->user_rating,
                'is_favorite' => $game->is_favorite,
                'cover' => $game->cover,
                'genres' => $game->genres
            ];
        })->toArray();
    }

    /**
     * Get compatibility rating based on score
     */
    private function getCompatibilityRating(float $score): string
    {
        if ($score >= 80) return 'Excellent Match';
        if ($score >= 60) return 'Great Match';
        if ($score >= 40) return 'Good Match';
        if ($score >= 20) return 'Fair Match';
        if ($score > 0) return 'Limited Match';
        return 'No Match';
    }
}
