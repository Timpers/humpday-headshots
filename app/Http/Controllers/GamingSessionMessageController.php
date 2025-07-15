<?php

namespace App\Http\Controllers;

use App\Models\GamingSession;
use App\Models\GamingSessionMessage;
use App\Notifications\GamingSessionMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GamingSessionMessageController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display messages for a gaming session
     */
    public function index(GamingSession $session): View|JsonResponse
    {
        // Check if user can view messages for this session
        $this->authorize('viewMessages', $session);

        $messages = GamingSessionMessage::where('gaming_session_id', $session->id)
            ->with('user:id,name')
            ->latest()
            ->paginate(50);

        if (request()->ajax()) {
            return response()->json([
                'messages' => $messages->items(),
                'has_more' => $messages->hasMorePages(),
                'next_page' => $messages->nextPageUrl()
            ]);
        }

        return view('gaming-sessions.messages', compact('session', 'messages'));
    }

    /**
     * Store a new message
     */
    public function store(Request $request, GamingSession $session): JsonResponse
    {
        // Check if user can post messages to this session
        $this->authorize('postMessage', $session);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'type' => 'sometimes|string|in:text,announcement,system'
        ]);

        $message = GamingSessionMessage::create([
            'gaming_session_id' => $session->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'text'
        ]);

        $message->load(['user:id,name', 'gamingSession']);

        // Notify all session participants except the sender
        $participants = $session->participantUsers()
            ->where('users.id', '!=', Auth::id())
            ->get();
        
        // Also notify the host if they're not the sender
        if ($session->host_user_id !== Auth::id()) {
            $participants = $participants->push($session->host);
        }

        // Send notifications
        if ($participants->isNotEmpty()) {
            Notification::send($participants, new GamingSessionMessageNotification($message));
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('gaming-sessions.partials.message', compact('message'))->render()
        ]);
    }

    /**
     * Update a message
     */
    public function update(Request $request, GamingSession $session, GamingSessionMessage $message): JsonResponse
    {
        // Check if user can edit this message
        $this->authorize('update', $message);

        // Ensure message belongs to the session
        if ($message->gaming_session_id !== $session->id) {
            abort(404);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $message->update([
            'message' => $validated['message'],
            'edited_at' => now()
        ]);

        $message->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('gaming-sessions.partials.message', compact('message'))->render()
        ]);
    }

    /**
     * Delete a message
     */
    public function destroy(GamingSession $session, GamingSessionMessage $message): JsonResponse
    {
        // Check if user can delete this message
        $this->authorize('delete', $message);

        // Ensure message belongs to the session
        if ($message->gaming_session_id !== $session->id) {
            abort(404);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * Get messages since a specific timestamp (for live updates)
     */
    public function recent(Request $request, GamingSession $session): JsonResponse
    {
        $this->authorize('viewMessages', $session);

        $since = $request->query('since');
        
        $query = GamingSessionMessage::where('gaming_session_id', $session->id)
            ->with('user:id,name')
            ->latest();

        if ($since) {
            $query->where('created_at', '>', $since);
        }

        $messages = $query->get();

        return response()->json([
            'messages' => $messages,
            'html' => $messages->map(function ($message) {
                return view('gaming-sessions.partials.message', compact('message'))->render();
            })
        ]);
    }
}
