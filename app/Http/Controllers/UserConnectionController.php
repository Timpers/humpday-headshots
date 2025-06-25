<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserConnectionController extends Controller
{
    /**
     * Send a connection request
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $currentUser = Auth::user();
        $recipientId = $request->recipient_id;

        // Prevent self-connection
        if ($currentUser->id === $recipientId) {
            return back()->withErrors(['connection' => 'You cannot connect with yourself.']);
        }

        // Check if connection already exists
        $existingConnection = UserConnection::where(function ($query) use ($currentUser, $recipientId) {
            $query->where('requester_id', $currentUser->id)
                  ->where('recipient_id', $recipientId);
        })->orWhere(function ($query) use ($currentUser, $recipientId) {
            $query->where('requester_id', $recipientId)
                  ->where('recipient_id', $currentUser->id);
        })->first();

        if ($existingConnection) {
            if ($existingConnection->status === UserConnection::STATUS_PENDING) {
                return back()->withErrors(['connection' => 'A connection request is already pending.']);
            } elseif ($existingConnection->status === UserConnection::STATUS_ACCEPTED) {
                return back()->withErrors(['connection' => 'You are already connected with this user.']);
            } elseif ($existingConnection->status === UserConnection::STATUS_BLOCKED) {
                return back()->withErrors(['connection' => 'Connection is not available.']);
            }
        }

        // Create the connection request
        UserConnection::create([
            'requester_id' => $currentUser->id,
            'recipient_id' => $recipientId,
            'message' => $request->message,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        return back()->with('success', 'Connection request sent successfully!');
    }

    /**
     * Accept a connection request
     */
    public function accept(UserConnection $connection)
    {
        $currentUser = Auth::user();

        // Verify the current user is the recipient
        if ($connection->recipient_id !== $currentUser->id) {
            abort(403, 'Unauthorized action.');
        }

        // Update the connection
        $connection->update([
            'status' => UserConnection::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        return back()->with('success', 'Connection request accepted!');
    }

    /**
     * Decline a connection request
     */
    public function decline(UserConnection $connection)
    {
        $currentUser = Auth::user();

        // Verify the current user is the recipient
        if ($connection->recipient_id !== $currentUser->id) {
            abort(403, 'Unauthorized action.');
        }

        $connection->update([
            'status' => UserConnection::STATUS_DECLINED,
        ]);

        return back()->with('success', 'Connection request declined.');
    }

    /**
     * Cancel a sent connection request
     */
    public function cancel(UserConnection $connection)
    {
        $currentUser = Auth::user();

        // Verify the current user is the requester
        if ($connection->requester_id !== $currentUser->id) {
            abort(403, 'Unauthorized action.');
        }

        $connection->delete();

        return back()->with('success', 'Connection request cancelled.');
    }

    /**
     * Block a user
     */
    public function block(UserConnection $connection)
    {
        $currentUser = Auth::user();

        // Verify the current user is involved in the connection
        if ($connection->requester_id !== $currentUser->id && $connection->recipient_id !== $currentUser->id) {
            abort(403, 'Unauthorized action.');
        }

        $connection->update([
            'status' => UserConnection::STATUS_BLOCKED,
        ]);

        return back()->with('success', 'User blocked.');
    }

    /**
     * Remove a connection (unfriend)
     */
    public function destroy(UserConnection $connection)
    {
        $currentUser = Auth::user();

        // Verify the current user is involved in the connection
        if ($connection->requester_id !== $currentUser->id && $connection->recipient_id !== $currentUser->id) {
            abort(403, 'Unauthorized action.');
        }

        $connection->delete();

        return back()->with('success', 'Connection removed.');
    }

    /**
     * Show the form for creating a new connection request
     */
    public function create(Request $request)
    {
        $user = null;
        $availableUsers = collect();

        // If user_id is provided, load that specific user
        if ($request->has('user_id')) {
            $user = User::with('gamertags')->findOrFail($request->user_id);

            // Check if current user is trying to connect with themselves
            if ($user->id === Auth::id()) {
                return redirect()->route('social.browse')->withErrors(['connection' => 'You cannot connect with yourself.']);
            }

            // Check if connection already exists
            $existingConnection = UserConnection::where(function ($query) use ($user) {
                $query->where('requester_id', Auth::id())
                      ->where('recipient_id', $user->id);
            })->orWhere(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                      ->where('recipient_id', Auth::id());
            })->first();

            if ($existingConnection) {
                return redirect()->route('social.browse')->withErrors(['connection' => 'A connection already exists with this user.']);
            }
        } else {
            // Get users available for connection (excluding current user and existing connections)
            $connectedUserIds = UserConnection::where(function ($query) {
                $query->where('requester_id', Auth::id())
                      ->orWhere('recipient_id', Auth::id());
            })->get()->pluck('requester_id', 'recipient_id')->flatten()->unique();

            $availableUsers = User::whereNotIn('id', $connectedUserIds->push(Auth::id()))->get();
        }

        return view('social.connection-request', compact('user', 'availableUsers'));
    }
}
