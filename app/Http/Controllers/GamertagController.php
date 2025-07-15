<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Gamertag;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class GamertagController extends Controller
{
    /**
     * Display a listing of all public gamertags.
     */
    public function index(): View
    {
        $gamertags = Gamertag::with('user')
            ->public()
            ->orderBy('platform')
            ->orderBy('gamertag')
            ->paginate(20);

        return view('gamertags.index', compact('gamertags'));
    }

    /**
     * Display gamertags for a specific user.
     */
    public function userGamertags(User $user): View
    {
        $gamertags = $user->publicGamertags()
            ->orderBy('platform')
            ->get()
            ->groupBy('platform');

        return view('gamertags.user', compact('user', 'gamertags'));
    }

    /**
     * Display gamertags for a specific platform.
     */
    public function platform(string $platform): View
    {
        if (!array_key_exists($platform, Gamertag::PLATFORMS)) {
            abort(404);
        }

        $gamertags = Gamertag::with('user')
            ->platform($platform)
            ->public()
            ->orderBy('gamertag')
            ->paginate(20);

        $platformName = Gamertag::PLATFORMS[$platform];

        return view('gamertags.platform', compact('gamertags', 'platform', 'platformName'));
    }

    /**
     * Store a newly created gamertag.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:' . implode(',', array_keys(Gamertag::PLATFORMS)),
            'gamertag' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        // Convert checkbox values
        $validated['is_public'] = $request->has('is_public');
        $validated['is_primary'] = $request->has('is_primary');

        /** @var User $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $gamertag = $user->gamertags()->create($validated);

        // Check if this is an AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Gamertag created successfully!',
                'gamertag' => $gamertag->load('user'),
            ], 201);
        }

        return redirect()->route('dashboard')->with('success', 'Gamertag added successfully!');
    }

    /**
     * Update the specified gamertag.
     */
    public function update(Request $request, Gamertag $gamertag)
    {
        // Ensure user can only update their own gamertags
        if ($gamertag->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'gamertag' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        // Convert checkbox values
        $validated['is_public'] = $request->has('is_public');
        $validated['is_primary'] = $request->has('is_primary');

        $gamertag->update($validated);

        // Check if this is an AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Gamertag updated successfully!',
                'gamertag' => $gamertag->fresh(),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Gamertag updated successfully!');
    }

    /**
     * Remove the specified gamertag.
     */
    public function destroy(Request $request, Gamertag $gamertag)
    {
        // Ensure user can only delete their own gamertags
        if ($gamertag->user_id !== Auth::id()) {
            abort(403);
        }

        $gamertag->delete();

        // Check if this is an AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Gamertag deleted successfully!',
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Gamertag deleted successfully!');
    }

    /**
     * Show the form for creating a new gamertag.
     */
    public function create(): View
    {
        return view('gamertags.create');
    }

    /**
     * Show the form for editing the specified gamertag.
     */
    public function edit(Gamertag $gamertag): View
    {
        // Ensure user can only edit their own gamertags
        if ($gamertag->user_id !== Auth::id()) {
            abort(403);
        }

        return view('gamertags.edit', compact('gamertag'));
    }
}
