<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MarcReichel\IGDBLaravel\Models\Game as IGDBGame;
use MarcReichel\IGDBLaravel\Models\Cover;
use MarcReichel\IGDBLaravel\Models\Screenshot;
use MarcReichel\IGDBLaravel\Models\Genre;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status');
        $platform = $request->get('platform');

        $games = $user->games()
            ->when($status, function ($query, $status) {
                return $query->byStatus($status);
            })
            ->when($platform, function ($query, $platform) {
                return $query->byPlatform($platform);
            })
            ->orderBy('name')
            ->paginate(12);

        $stats = [
            'total' => $user->games()->count(),
            'owned' => $user->games()->byStatus(Game::STATUS_OWNED)->count(),
            'wishlist' => $user->games()->byStatus(Game::STATUS_WISHLIST)->count(),
            'playing' => $user->games()->byStatus(Game::STATUS_PLAYING)->count(),
            'completed' => $user->games()->byStatus(Game::STATUS_COMPLETED)->count(),
        ];

        return view('games.index', compact('games', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('games.create');
    }

    /**
     * Search games via IGDB API
     */
    public function search(Request $request)
    {
        $request->validate([
            'search_query' => 'required|string|min:2',
        ]);

        try {
            $games = IGDBGame::search($request->search_query)
                ->with(['cover', 'genres', 'platforms'])
                ->limit(10)
                ->get();

            $formattedGames = $games->map(function ($game) {

                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'summary' => $game->summary ?? '',
                    'slug' => $game->slug ?? '',
                    'cover' => $game->cover ? [
                        'id' => $game->cover->id,
                        'url' => $game->cover->url,
                    ] : null,
                    'genres' => $game->genres ? $game->genres->map(function ($genre) {
                        return ['id' => $genre->id, 'name' => $genre->name];
                    })->toArray() : [],
                    'platforms' => $game->platforms ? $game->platforms->map(function ($platform) {
                        return ['id' => $platform->id, 'name' => $platform->name];
                    })->toArray() : [],
                    'first_release_date' => $game->first_release_date ? $game->first_release_date : null,
                    'rating' => $game->rating ? round($game->rating / 10, 1) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'games' => $formattedGames,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search games: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'igdb_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'platform' => 'required|string|in:' . implode(',', array_keys(Game::PLATFORMS)),
            'status' => 'required|string|in:' . implode(',', array_keys(Game::STATUSES)),
            'user_rating' => 'nullable|numeric|between:1,10',
            'notes' => 'nullable|string|max:1000',
            'hours_played' => 'nullable|integer|min:0',
            'date_purchased' => 'nullable|date',
            'price_paid' => 'nullable|numeric|min:0',
            'is_digital' => 'boolean',
            'is_completed' => 'boolean',
            'is_favorite' => 'boolean',
        ]);

        $user = Auth::user();

        // Check if user already has this game for this platform
        $existingGame = $user->games()
            ->where('igdb_id', $request->igdb_id)
            ->where('platform', $request->platform)
            ->first();

        if ($existingGame) {
            return back()->withErrors(['game' => 'You already have this game for this platform.']);
        }

        // Create the game record
        $gameData = $request->only([
            'igdb_id', 'name', 'platform', 'status', 'user_rating',
            'notes', 'hours_played', 'date_purchased', 'price_paid'
        ]);

        $gameData['user_id'] = $user->id;
        $gameData['is_digital'] = $request->boolean('is_digital', true);
        $gameData['is_completed'] = $request->boolean('is_completed', false);
        $gameData['is_favorite'] = $request->boolean('is_favorite', false);

        // Add IGDB data if provided
        if ($request->has('igdb_data')) {
            $igdbData = json_decode($request->igdb_data, true);
            $gameData = array_merge($gameData, [
                'summary' => $igdbData['summary'] ?? null,
                'slug' => $igdbData['slug'] ?? null,
                'cover' => $igdbData['cover'] ?? null,
                'genres' => $igdbData['genres'] ?? null,
                'platforms' => $igdbData['platforms'] ?? null,
                'release_date' => $igdbData['first_release_date'] ?? null,
                'rating' => $igdbData['rating'] ?? null,
            ]);
        }

        $game = Game::create($gameData);

        return redirect()->route('games.index')
            ->with('success', 'Game added to your library successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Game $game)
    {
        // Check if the game belongs to the authenticated user
        if ($game->user_id !== Auth::id()) {
            abort(403);
        }

        return view('games.show', compact('game'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Game $game)
    {
        // Check if the game belongs to the authenticated user
        if ($game->user_id !== Auth::id()) {
            abort(403);
        }

        return view('games.edit', compact('game'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Game $game)
    {
        // Check if the game belongs to the authenticated user
        if ($game->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'platform' => 'required|string|in:' . implode(',', array_keys(Game::PLATFORMS)),
            'status' => 'required|string|in:' . implode(',', array_keys(Game::STATUSES)),
            'user_rating' => 'nullable|numeric|between:1,10',
            'notes' => 'nullable|string|max:1000',
            'hours_played' => 'nullable|integer|min:0',
            'date_purchased' => 'nullable|date',
            'price_paid' => 'nullable|numeric|min:0',
            'is_digital' => 'boolean',
            'is_completed' => 'boolean',
            'is_favorite' => 'boolean',
        ]);

        $gameData = $request->only([
            'platform', 'status', 'user_rating', 'notes', 'hours_played',
            'date_purchased', 'price_paid'
        ]);

        $gameData['is_digital'] = $request->boolean('is_digital');
        $gameData['is_completed'] = $request->boolean('is_completed');
        $gameData['is_favorite'] = $request->boolean('is_favorite');

        $game->update($gameData);

        return redirect()->route('games.show', $game)
            ->with('success', 'Game updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game $game)
    {
        // Check if the game belongs to the authenticated user
        if ($game->user_id !== Auth::id()) {
            abort(403);
        }

        $game->delete();

        return redirect()->route('games.index')
            ->with('success', 'Game removed from your library.');
    }
}
