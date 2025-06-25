<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Auth::user()
            ->unreadNotifications()
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? 'general',
                    'title' => $this->getNotificationTitle($notification),
                    'message' => $this->getNotificationMessage($notification),
                    'url' => $notification->data['url'] ?? '#',
                    'created_at' => $notification->created_at->diffForHumans(),
                    'data' => $notification->data,
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = Auth::user()
            ->unreadNotifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => Auth::user()->unreadNotifications()->count()
        ]);
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
            'subscription.keys' => 'required|array',
            'subscription.keys.p256dh' => 'required|string',
            'subscription.keys.auth' => 'required|string',
        ]);

        $user = Auth::user();
        
        // Store subscription data (you might want to create a dedicated table for this)
        $user->update([
            'push_subscription' => json_encode($request->subscription),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Test push notifications (for development/demo purposes)
     */
    public function test(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Create a simple test notification using the database
        \Illuminate\Support\Facades\DB::table('notifications')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'type' => 'test',
                'title' => 'Test Notification',
                'message' => 'This is a test notification to verify push functionality is working.',
                'url' => route('dashboard'),
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Test notification sent!']);
    }

    /**
     * Get notification title based on type
     */
    private function getNotificationTitle($notification): string
    {
        return match($notification->data['type'] ?? 'general') {
            'gaming_session_message' => 'New Message',
            'connection_request' => match($notification->data['action'] ?? 'sent') {
                'sent' => 'Connection Request',
                'accepted' => 'Connection Accepted',
                'declined' => 'Connection Declined',
                default => 'Connection Update'
            },
            'group_invitation' => match($notification->data['action'] ?? 'sent') {
                'sent' => 'Group Invitation',
                'accepted' => 'Invitation Accepted',
                'declined' => 'Invitation Declined',
                default => 'Group Update'
            },
            'gaming_session_invitation' => match($notification->data['action'] ?? 'sent') {
                'sent' => 'Session Invitation',
                'accepted' => 'Invitation Accepted',
                'declined' => 'Invitation Declined',
                default => 'Session Update'
            },
            default => 'Notification'
        };
    }

    /**
     * Get notification message based on type
     */
    private function getNotificationMessage($notification): string
    {
        return match($notification->data['type'] ?? 'general') {
            'gaming_session_message' => ($notification->data['sender_name'] ?? 'Someone') . ' posted in ' . ($notification->data['session_title'] ?? 'a session'),
            'connection_request' => match($notification->data['action'] ?? 'sent') {
                'sent' => ($notification->data['requester_name'] ?? 'Someone') . ' wants to connect',
                'accepted' => ($notification->data['requester_name'] ?? 'Someone') . ' accepted your request',
                'declined' => ($notification->data['requester_name'] ?? 'Someone') . ' declined your request',
                default => 'Connection update'
            },
            'group_invitation' => match($notification->data['action'] ?? 'sent') {
                'sent' => 'Invited to join ' . ($notification->data['group_name'] ?? 'a group'),
                'accepted' => ($notification->data['inviter_name'] ?? 'Someone') . ' joined ' . ($notification->data['group_name'] ?? 'the group'),
                'declined' => ($notification->data['inviter_name'] ?? 'Someone') . ' declined to join ' . ($notification->data['group_name'] ?? 'the group'),
                default => 'Group update'
            },
            'gaming_session_invitation' => match($notification->data['action'] ?? 'sent') {
                'sent' => 'Invited to play ' . ($notification->data['game_name'] ?? 'a game'),
                'accepted' => ($notification->data['host_name'] ?? 'Someone') . ' will join your session',
                'declined' => ($notification->data['host_name'] ?? 'Someone') . ' declined your session',
                default => 'Session update'
            },
            default => 'You have a new notification'
        };
    }
}
