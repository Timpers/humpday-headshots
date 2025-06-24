<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Gamertag;
use App\Models\UserConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocialController extends Controller
{
    /**
     * Helper method to get connection status between two users
     */
    private function getConnectionStatusBetweenUsers($userId1, $userId2)
    {
        $connection = UserConnection::where(function ($query) use ($userId1, $userId2) {
            $query->where('requester_id', $userId1)
                  ->where('recipient_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('requester_id', $userId2)
                  ->where('recipient_id', $userId1);
        })->first();

        if (!$connection) {
            return null;
        }

        return [
            'status' => $connection->status,
            'is_requester' => $connection->requester_id === $userId1,
            'connection' => $connection,
        ];
    }

    /**
     * Show the social hub page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's connections stats using raw queries for now
        $stats = [
            'friends' => UserConnection::where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                      ->orWhere('recipient_id', $user->id);
            })->where('status', UserConnection::STATUS_ACCEPTED)->count(),
            
            'pending_received' => UserConnection::where('recipient_id', $user->id)
                ->where('status', UserConnection::STATUS_PENDING)->count(),
                
            'pending_sent' => UserConnection::where('requester_id', $user->id)
                ->where('status', UserConnection::STATUS_PENDING)->count(),
        ];

        // Get recent connection requests
        $pendingRequests = UserConnection::where('recipient_id', $user->id)
            ->where('status', UserConnection::STATUS_PENDING)
            ->with('requester.gamertags')
            ->latest()
            ->limit(5)
            ->get();

        // Get recent friends
        $friendConnections = UserConnection::where(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                  ->orWhere('recipient_id', $user->id);
        })
        ->where('status', UserConnection::STATUS_ACCEPTED)
        ->with(['requester.gamertags', 'recipient.gamertags'])
        ->latest('accepted_at')
        ->limit(6)
        ->get();

        $recentFriends = $friendConnections->map(function ($connection) use ($user) {
            return $connection->getOtherUser($user->id);
        });

        return view('social.index', compact('stats', 'pendingRequests', 'recentFriends'));
    }

    /**
     * Enhanced search for users and gamertags with platform filtering
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|min:1|max:50',
            'type' => 'in:users,gamertags,all',
            'platform' => 'nullable|string|in:steam,xbox_live,playstation_network,nintendo_online,battlenet',
        ]);

        $query = $request->input('query', '');
        $type = $request->input('type', 'all');
        $platform = $request->input('platform');
        $currentUser = Auth::user();

        $results = [];

        if ($type === 'users' || $type === 'all') {
            // Enhanced user search with platform filtering
            $userQuery = User::where('id', '!=', $currentUser->id);
            
            if (!empty($query)) {
                $userQuery->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                });
            }

            // Filter by platform if specified
            if ($platform) {
                $userQuery->whereHas('gamertags', function ($q) use ($platform) {
                    $q->where('platform', $platform)->where('is_public', true);
                });
            } else {
                // Only include users with at least one public gamertag
                $userQuery->whereHas('gamertags', function ($q) {
                    $q->where('is_public', true);
                });
            }

            $users = $userQuery->with(['gamertags' => function ($q) use ($platform) {
                    $q->where('is_public', true);
                    if ($platform) {
                        $q->where('platform', $platform);
                    }
                    $q->limit(5);
                }])
                ->limit(15)
                ->get();

            $results['users'] = $users->map(function ($user) use ($currentUser) {
                $connectionStatus = $this->getConnectionStatusBetweenUsers($currentUser->id, $user->id);
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'gamertags' => $user->gamertags,
                    'gamertags_count' => $user->gamertags->count(),
                    'connection_status' => $connectionStatus,
                ];
            });
        }

        if ($type === 'gamertags' || $type === 'all') {
            // Enhanced gamertag search
            $gamertagQuery = Gamertag::where('is_public', true)
                ->where('user_id', '!=', $currentUser->id);

            if (!empty($query)) {
                $gamertagQuery->where(function ($q) use ($query) {
                    $q->where('gamertag', 'like', "%{$query}%")
                      ->orWhere('display_name', 'like', "%{$query}%");
                });
            }

            if ($platform) {
                $gamertagQuery->where('platform', $platform);
            }

            $gamertags = $gamertagQuery->with('user')
                ->orderBy('is_primary', 'desc')
                ->orderBy('gamertag')
                ->limit(20)
                ->get();

            $results['gamertags'] = $gamertags->map(function ($gamertag) use ($currentUser) {
                $connectionStatus = $this->getConnectionStatusBetweenUsers($currentUser->id, $gamertag->user_id);
                return [
                    'id' => $gamertag->id,
                    'gamertag' => $gamertag->gamertag,
                    'display_name' => $gamertag->display_name,
                    'platform' => $gamertag->platform,
                    'platform_formatted' => $gamertag->formatted_platform,
                    'is_primary' => $gamertag->is_primary,
                    'user' => [
                        'id' => $gamertag->user->id,
                        'name' => $gamertag->user->name,
                        'email' => $gamertag->user->email,
                    ],
                    'connection_status' => $connectionStatus,
                ];
            });
        }

        // Get available platforms for filtering
        $platforms = collect(['steam', 'xbox_live', 'playstation_network', 'nintendo_online', 'battlenet'])
            ->mapWithKeys(function ($platform) {
                return [$platform => Gamertag::PLATFORMS[$platform] ?? ucfirst($platform)];
            });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'results' => $results,
                'platforms' => $platforms,
                'query' => $query,
                'type' => $type,
                'platform' => $platform,
            ]);
        }

        return view('social.search', compact('results', 'platforms', 'query', 'type', 'platform'));
    }

    /**
     * Show all users (browse page)
     */
    public function browse(Request $request)
    {
        $currentUser = Auth::user();
        $platform = $request->get('platform');

        $users = User::where('id', '!=', $currentUser->id)
            ->withCount(['gamertags' => function ($q) {
                $q->where('is_public', true);
            }])
            ->having('gamertags_count', '>', 0)
            ->when($platform, function ($query, $platform) {
                return $query->whereHas('gamertags', function ($q) use ($platform) {
                    $q->where('platform', $platform)->where('is_public', true);
                });
            })
            ->with(['gamertags' => function ($q) use ($platform) {
                $q->where('is_public', true);
                if ($platform) {
                    $q->where('platform', $platform);
                }
                $q->limit(3);
            }])
            ->paginate(12);

        $platforms = Gamertag::where('is_public', true)
            ->distinct()
            ->pluck('platform')
            ->sort();

        return view('social.browse', compact('users', 'platforms', 'platform'));
    }

    /**
     * Show user's friends
     */
    public function friends()
    {
        $user = Auth::user();
        
        $friends = UserConnection::where(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                  ->orWhere('recipient_id', $user->id);
        })
        ->where('status', UserConnection::STATUS_ACCEPTED)
        ->with(['requester.gamertags', 'recipient.gamertags'])
        ->latest('accepted_at')
        ->paginate(12);

        $friendUsers = $friends->map(function ($connection) use ($user) {
            return $connection->getOtherUser($user->id);
        });

        return view('social.friends', compact('friends', 'friendUsers'));
    }

    /**
     * Show pending connection requests
     */
    public function requests()
    {
        $user = Auth::user();
        
        $receivedRequests = UserConnection::where('recipient_id', $user->id)
            ->where('status', UserConnection::STATUS_PENDING)
            ->with('requester.gamertags')
            ->latest()
            ->get();

        $sentRequests = UserConnection::where('requester_id', $user->id)
            ->where('status', UserConnection::STATUS_PENDING)
            ->with('recipient.gamertags')
            ->latest()
            ->get();

        return view('social.requests', compact('receivedRequests', 'sentRequests'));
    }
}
