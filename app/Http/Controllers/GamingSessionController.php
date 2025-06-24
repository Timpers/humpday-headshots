<?php

namespace App\Http\Controllers;

use App\Models\GamingSession;
use App\Models\GamingSessionInvitation;
use App\Models\GamingSessionParticipant;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use MarcReichel\IGDBLaravel\Models\Game;

class GamingSessionController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of gaming sessions.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = GamingSession::with(['host', 'participants']);

        // Filter by type
        $type = $request->get('type', 'all');
        if ($type === 'hosting') {
            $query->where('host_user_id', $user->id);
        } elseif ($type === 'participating') {
            $query->whereHas('participantUsers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($type === 'invited') {
            $query->whereHas('invitations', function ($q) use ($user) {
                $q->where('invited_user_id', $user->id)
                  ->where('status', GamingSessionInvitation::STATUS_PENDING);
            });
        } elseif ($type === 'public') {
            $query->public();
        } else {
            // All sessions user is involved in
            $query->forUser($user);
        }

        // Filter by status
        $status = $request->get('status');
        if ($status) {
            $query->where('status', $status);
        } else {
            // Default to upcoming sessions
            $query->upcoming();
        }

        // Search by game or title
        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('game_name', 'like', '%' . $search . '%');
            });
        }

        $sessions = $query->orderBy('scheduled_at', 'asc')->paginate(12);

        return view('gaming-sessions.index', compact('sessions', 'type', 'status', 'search'));
    }

    /**
     * Show the form for creating a new gaming session.
     */    public function create()
    {
        $user = Auth::user();
        $friends = $user->friendUsers();
        $groups = $user->groups()->get();

        return view('gaming-sessions.create', compact('friends', 'groups'));
    }

    /**
     * Store a newly created gaming session.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'game_name' => 'required|string|max:255',
            'platform' => 'nullable|string|max:50',
            'scheduled_at' => 'required|date|after:now',
            'max_participants' => 'required|integer|min:2|max:50',
            'privacy' => 'required|in:public,friends_only,invite_only',
            'requirements' => 'nullable|string|max:1000',
            'invite_friends' => 'nullable|array',
            'invite_friends.*' => 'exists:users,id',
            'invite_groups' => 'nullable|array',
            'invite_groups.*' => 'exists:groups,id',
        ]);

        DB::transaction(function () use ($request) {
            // Create the gaming session
            $session = GamingSession::create([
                'host_user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
                'game_name' => $request->game_name,
                'platform' => $request->platform,
                'scheduled_at' => $request->scheduled_at,
                'max_participants' => $request->max_participants,
                'privacy' => $request->privacy,
                'requirements' => $request->requirements,
            ]);

            // Host automatically joins the session
            GamingSessionParticipant::create([
                'gaming_session_id' => $session->id,
                'user_id' => Auth::id(),
                'joined_at' => now(),
            ]);

            // Send invitations to friends
            if ($request->invite_friends) {
                foreach ($request->invite_friends as $friendId) {
                    GamingSessionInvitation::create([
                        'gaming_session_id' => $session->id,
                        'invited_user_id' => $friendId,
                        'invited_by_user_id' => Auth::id(),
                    ]);
                }
            }

            // Send invitations to groups
            if ($request->invite_groups) {
                foreach ($request->invite_groups as $groupId) {
                    GamingSessionInvitation::create([
                        'gaming_session_id' => $session->id,
                        'invited_group_id' => $groupId,
                        'invited_by_user_id' => Auth::id(),
                    ]);
                }
            }
        });

        return redirect()->route('gaming-sessions.index')
                        ->with('success', 'Gaming session created successfully!');
    }

    /**
     * Display the specified gaming session.
     */
    public function show(GamingSession $gamingSession)
    {
        $user = Auth::user();
        
        $gamingSession->load([
            'host',
            'participants.user',
            'invitations.invitedUser',
            'invitations.invitedGroup',
            'invitations.invitedBy'
        ]);

        $userInvitation = $gamingSession->invitations()
                                      ->where('invited_user_id', $user->id)
                                      ->first();

        $isParticipant = $gamingSession->participantUsers()
                                     ->where('user_id', $user->id)
                                     ->exists();

        $canJoin = $gamingSession->canUserJoin($user);

        return view('gaming-sessions.show', compact(
            'gamingSession',
            'userInvitation',
            'isParticipant',
            'canJoin'
        ));
    }

    /**
     * Show the form for editing the gaming session.
     */    public function edit(GamingSession $gamingSession)
    {
        $this->authorize('update', $gamingSession);
        
        $user = Auth::user();
        $friends = $user->friendUsers();
        $groups = $user->groups()->get();

        return view('gaming-sessions.edit', compact('gamingSession', 'friends', 'groups'));
    }

    /**
     * Update the specified gaming session.
     */
    public function update(Request $request, GamingSession $gamingSession)
    {
        $this->authorize('update', $gamingSession);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'game_name' => 'required|string|max:255',
            'platform' => 'nullable|string|max:50',
            'scheduled_at' => 'required|date|after:now',
            'max_participants' => 'required|integer|min:2|max:50',
            'privacy' => 'required|in:public,friends_only,invite_only',
            'requirements' => 'nullable|string|max:1000',
        ]);

        $gamingSession->update($request->only([
            'title',
            'description',
            'game_name',
            'platform',
            'scheduled_at',
            'max_participants',
            'privacy',
            'requirements',
        ]));

        return redirect()->route('gaming-sessions.show', $gamingSession)
                        ->with('success', 'Gaming session updated successfully!');
    }

    /**
     * Remove the specified gaming session.
     */
    public function destroy(GamingSession $gamingSession)
    {
        $this->authorize('delete', $gamingSession);

        $gamingSession->update(['status' => GamingSession::STATUS_CANCELLED]);

        return redirect()->route('gaming-sessions.index')
                        ->with('success', 'Gaming session cancelled successfully!');
    }

    /**
     * Join a gaming session.
     */
    public function join(GamingSession $gamingSession)
    {
        $user = Auth::user();

        if (!$gamingSession->canUserJoin($user)) {
            return back()->with('error', 'You cannot join this gaming session.');
        }

        GamingSessionParticipant::create([
            'gaming_session_id' => $gamingSession->id,
            'user_id' => $user->id,
            'joined_at' => now(),
        ]);

        return back()->with('success', 'You have joined the gaming session!');
    }

    /**
     * Leave a gaming session.
     */
    public function leave(GamingSession $gamingSession)
    {
        $user = Auth::user();

        $participant = $gamingSession->participants()
                                   ->where('user_id', $user->id)
                                   ->first();

        if (!$participant) {
            return back()->with('error', 'You are not a participant in this session.');
        }

        // Host cannot leave their own session
        if ($gamingSession->host_user_id === $user->id) {
            return back()->with('error', 'You cannot leave your own gaming session. You can cancel it instead.');
        }

        $participant->leave();

        return back()->with('success', 'You have left the gaming session.');
    }    /**
     * Search for games using IGDB API.
     */
    public function searchGames(Request $request)
    {
        $query = $request->get('query');
        
        Log::info('IGDB Search Request', ['query' => $query]);
        
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        try {
            $games = Game::where('name', 'ilike', "*{$query}*")
                        ->select(['id', 'name', 'cover', 'platforms'])
                        ->with(['cover', 'platforms'])
                        ->limit(10)
                        ->get();

            Log::info('IGDB Search Response', ['count' => $games->count()]);

            $results = $games->map(function ($game) {
                $coverUrl = null;
                if ($game->cover && isset($game->cover->url)) {
                    $coverUrl = 'https:' . str_replace('t_thumb', 't_cover_small', $game->cover->url);
                }

                $platforms = [];
                if ($game->platforms && is_array($game->platforms)) {
                    $platforms = collect($game->platforms)->pluck('name')->filter()->toArray();
                }

                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'cover_url' => $coverUrl,
                    'platforms' => $platforms,
                ];
            });

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('IGDB Search Error: ' . $e->getMessage(), [
                'query' => $query,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to search games: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Respond to a gaming session invitation.
     */
    public function respondToInvitation(Request $request, GamingSessionInvitation $invitation)
    {
        $user = Auth::user();

        if ($invitation->invited_user_id !== $user->id) {
            return back()->with('error', 'This invitation is not for you.');
        }

        if ($invitation->status !== GamingSessionInvitation::STATUS_PENDING) {
            return back()->with('error', 'This invitation has already been responded to.');
        }

        $action = $request->get('action');

        if ($action === 'accept') {
            $invitation->accept();
            return back()->with('success', 'Invitation accepted! You have joined the gaming session.');
        } elseif ($action === 'decline') {
            $invitation->decline();
            return back()->with('success', 'Invitation declined.');
        }

        return back()->with('error', 'Invalid action.');
    }
}
