<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserConnection;
use App\Notifications\ConnectionRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Test User']);
        $this->otherUser = User::factory()->create(['name' => 'Other User']);
    }

    public function test_index_returns_user_notifications()
    {
        // Create some test notifications
        $this->createTestNotifications();

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'notifications' => [
                '*' => [
                    'id',
                    'type',
                    'title',
                    'message',
                    'url',
                    'created_at',
                    'data'
                ]
            ],
            'unread_count'
        ]);

        $notifications = $response->json('notifications');
        $this->assertCount(3, $notifications);
        $this->assertEquals(3, $response->json('unread_count'));
    }

    public function test_index_limits_notifications_to_20()
    {
        // Create 25 test notifications
        for ($i = 0; $i < 25; $i++) {
            $this->createNotification([
                'type' => 'connection_request',
                'title' => 'Connection Request',
                'message' => "Test notification {$i}",
                'url' => route('social.requests'),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertCount(20, $notifications);
    }

    public function test_index_formats_notification_data_correctly()
    {
        $this->createNotification([
            'type' => 'connection_request',
            'action' => 'sent',
            'requester_name' => 'John Doe',
            'url' => route('social.requests'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notification = $response->json('notifications.0');
        $this->assertEquals('connection_request', $notification['type']);
        $this->assertEquals('Connection Request', $notification['title']);
        $this->assertEquals('John Doe wants to connect', $notification['message']);
        $this->assertArrayHasKey('created_at', $notification);
        $this->assertArrayHasKey('data', $notification);
    }

    public function test_index_handles_different_notification_types()
    {
        // Test different notification types
        $this->createNotification([
            'type' => 'gaming_session_message',
            'sender_name' => 'Alice',
            'session_title' => 'Epic Gaming Night',
            'url' => route('dashboard'),
        ]);

        $this->createNotification([
            'type' => 'group_invitation',
            'action' => 'sent',
            'group_name' => 'Pro Gamers',
            'url' => route('dashboard'),
        ]);

        $this->createNotification([
            'type' => 'gaming_session_invitation',
            'action' => 'accepted',
            'host_name' => 'Bob',
            'game_name' => 'Call of Duty',
            'url' => route('dashboard'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertCount(3, $notifications);

        // Check titles are formatted correctly
        $titles = array_column($notifications, 'title');
        $this->assertContains('New Message', $titles);
        $this->assertContains('Group Invitation', $titles);
        $this->assertContains('Invitation Accepted', $titles);
    }

    public function test_index_requires_authentication()
    {
        $response = $this->getJson(route('notifications.index'));

        $response->assertStatus(401);
    }

    public function test_mark_as_read_updates_notification()
    {
        $notification = $this->createNotification([
            'type' => 'connection_request',
            'title' => 'Test Notification',
            'url' => route('social.requests'),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.read', $notification));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify notification is marked as read
        $this->assertDatabaseHas('notifications', [
            'id' => $notification,
            'notifiable_id' => $this->user->id,
        ]);

        $updatedNotification = DB::table('notifications')
            ->where('id', $notification)
            ->first();
        
        $this->assertNotNull($updatedNotification->read_at);
    }

    public function test_mark_as_read_only_works_for_own_notifications()
    {
        $notification = $this->createNotification([
            'type' => 'connection_request',
            'title' => 'Test Notification',
            'url' => route('social.requests'),
        ], $this->otherUser);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.read', $notification));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Notification should remain unread since it doesn't belong to this user
        $updatedNotification = DB::table('notifications')
            ->where('id', $notification)
            ->first();
        
        $this->assertNull($updatedNotification->read_at);
    }

    public function test_mark_as_read_handles_nonexistent_notification()
    {
        $fakeId = Str::uuid();

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.read', $fakeId));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_mark_as_read_requires_authentication()
    {
        $notification = $this->createNotification([
            'type' => 'connection_request',
            'title' => 'Test Notification',
            'url' => route('social.requests'),
        ]);

        $response = $this->postJson(route('notifications.read', $notification));

        $response->assertStatus(401);
    }

    public function test_mark_all_as_read_updates_all_user_notifications()
    {
        // Create multiple unread notifications
        $this->createTestNotifications();

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.read-all'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all notifications are marked as read
        $unreadCount = $this->user->fresh()->unreadNotifications()->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function test_mark_all_as_read_only_affects_current_user()
    {
        // Create notifications for both users
        $this->createTestNotifications();
        $this->createNotification([
            'type' => 'connection_request',
            'title' => 'Other User Notification',
            'url' => route('social.requests'),
        ], $this->otherUser);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.read-all'));

        $response->assertStatus(200);

        // Current user notifications should be read
        $this->assertEquals(0, $this->user->fresh()->unreadNotifications()->count());
        
        // Other user notifications should remain unread
        $this->assertEquals(1, $this->otherUser->fresh()->unreadNotifications()->count());
    }

    public function test_mark_all_as_read_requires_authentication()
    {
        $response = $this->postJson(route('notifications.read-all'));

        $response->assertStatus(401);
    }

    public function test_unread_count_returns_correct_count()
    {
        $this->createTestNotifications();

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.count'));

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    }

    public function test_unread_count_updates_after_marking_as_read()
    {
        $this->createTestNotifications();

        // Mark all as read
        $this->user->unreadNotifications->markAsRead();

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.count'));

        $response->assertStatus(200);
        $response->assertJson(['count' => 0]);
    }

    public function test_unread_count_requires_authentication()
    {
        $response = $this->getJson(route('notifications.count'));

        $response->assertStatus(401);
    }

    public function test_subscribe_stores_push_subscription()
    {
        $subscriptionData = [
            'subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
                'keys' => [
                    'p256dh' => 'test-p256dh-key',
                    'auth' => 'test-auth-key'
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.subscribe'), $subscriptionData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify subscription is stored
        $this->user->refresh();
        $this->assertNotNull($this->user->push_subscription);
        
        $storedSubscription = is_string($this->user->push_subscription) 
            ? json_decode($this->user->push_subscription, true) 
            : $this->user->push_subscription;
        $this->assertEquals('https://fcm.googleapis.com/fcm/send/test', $storedSubscription['endpoint']);
    }

    public function test_subscribe_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.subscribe'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription']);
    }

    public function test_subscribe_validates_subscription_structure()
    {
        $invalidData = [
            'subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
                // Missing keys
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.subscribe'), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription.keys']);
    }

    public function test_subscribe_validates_subscription_keys()
    {
        $invalidData = [
            'subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
                'keys' => [
                    'p256dh' => 'test-p256dh-key',
                    // Missing auth key
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.subscribe'), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription.keys.auth']);
    }

    public function test_subscribe_requires_authentication()
    {
        $subscriptionData = [
            'subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
                'keys' => [
                    'p256dh' => 'test-p256dh-key',
                    'auth' => 'test-auth-key'
                ]
            ]
        ];

        $response = $this->postJson(route('notifications.subscribe'), $subscriptionData);

        $response->assertStatus(401);
    }

    public function test_test_creates_test_notification()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.test'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Test notification sent!'
        ]);

        // Verify test notification was created
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
            'type' => 'App\\Notifications\\TestNotification'
        ]);

        $notification = DB::table('notifications')
            ->where('notifiable_id', $this->user->id)
            ->where('type', 'App\\Notifications\\TestNotification')
            ->first();

        $data = json_decode($notification->data, true);
        $this->assertEquals('test', $data['type']);
        $this->assertEquals('Test Notification', $data['title']);
    }

    public function test_test_requires_authentication()
    {
        $response = $this->postJson(route('notifications.test'));

        $response->assertStatus(401);
    }

    public function test_notification_title_formatting_for_different_actions()
    {
        // Test different connection request actions
        $this->createNotification([
            'type' => 'connection_request',
            'action' => 'accepted',
            'url' => route('social.requests'),
        ]);

        $this->createNotification([
            'type' => 'connection_request',
            'action' => 'declined',
            'url' => route('social.requests'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $titles = array_column($notifications, 'title');
        
        $this->assertContains('Connection Accepted', $titles);
        $this->assertContains('Connection Declined', $titles);
    }

    public function test_notification_message_formatting_for_different_types()
    {
        $this->createNotification([
            'type' => 'group_invitation',
            'action' => 'accepted',
            'inviter_name' => 'Alice',
            'group_name' => 'Pro Squad',
            'url' => route('dashboard'),
        ]);

        $this->createNotification([
            'type' => 'gaming_session_invitation',
            'action' => 'declined',
            'host_name' => 'Bob',
            'url' => route('dashboard'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $messages = array_column($notifications, 'message');
        
        $this->assertContains('Alice joined Pro Squad', $messages);
        $this->assertContains('Bob declined your session', $messages);
    }

    private function createTestNotifications()
    {
        $this->createNotification([
            'type' => 'connection_request',
            'action' => 'sent',
            'requester_name' => 'John Doe',
            'url' => route('social.requests'),
        ]);

        $this->createNotification([
            'type' => 'gaming_session_message',
            'sender_name' => 'Alice',
            'session_title' => 'Epic Gaming Night',
            'url' => route('dashboard'),
        ]);

        $this->createNotification([
            'type' => 'group_invitation',
            'action' => 'sent',
            'group_name' => 'Pro Gamers',
            'url' => route('dashboard'),
        ]);
    }

    private function createNotification(array $data, ?User $user = null): string
    {
        $user = $user ?? $this->user;
        $notificationId = Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $user->id,
            'data' => json_encode($data),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $notificationId;
    }

    public function test_group_invitation_accepted_title_generation()
    {
        // Test line 141: 'accepted' => 'Invitation Accepted' in group_invitation match case
        $this->createNotification([
            'type' => 'group_invitation',
            'action' => 'accepted',
            'inviter_name' => 'John Doe',
            'group_name' => 'Elite Squad',
            'url' => route('dashboard'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        
        // Verify the specific title returned by line 141
        $this->assertEquals('Invitation Accepted', $notifications[0]['title']);
        $this->assertEquals('group_invitation', $notifications[0]['type']);
    }

    public function test_gaming_session_invitation_accepted_title_generation()
    {
        // Test line 145: 'accepted' => 'Invitation Accepted' in gaming_session_invitation match case
        $this->createNotification([
            'type' => 'gaming_session_invitation',
            'action' => 'accepted',
            'host_name' => 'Jane Smith',
            'game_name' => 'Call of Duty',
            'url' => route('dashboard'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        
        // Verify the specific title returned by line 145
        $this->assertEquals('Invitation Accepted', $notifications[0]['title']);
        $this->assertEquals('gaming_session_invitation', $notifications[0]['type']);
    }

        public function test_gaming_session_invitation_sent_title_generation()
    {
        // Test line 145: 'accepted' => 'Invitation Accepted' in gaming_session_invitation match case
        $this->createNotification([
            'type' => 'gaming_session_invitation',
            'action' => 'sent',
            'host_name' => 'Jane Smith',
            'game_name' => 'Call of Duty',
            'url' => route('dashboard'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        
        // Verify the specific title returned by line 145
        $this->assertEquals('Session Invitation', $notifications[0]['title']);
        $this->assertEquals('gaming_session_invitation', $notifications[0]['type']);
    }

    public function test_group_invitation_accepted_message_generation()
    {
        // Test line 170: message generation for group_invitation with accepted action
        $this->createNotification([
            'type' => 'group_invitation',
            'action' => 'accepted',
            'inviter_name' => 'Alice Johnson',
            'group_name' => 'Elite Gaming Squad',
            'url' => route('dashboard'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        
        // Verify the specific message returned by line 170
        $this->assertEquals('Alice Johnson joined Elite Gaming Squad', $notifications[0]['message']);
        $this->assertEquals('group_invitation', $notifications[0]['type']);
    }

        public function test_group_invitation_declined_message_generation()
    {
        // Test line 170: message generation for group_invitation with accepted action
        $this->createNotification([
            'type' => 'group_invitation',
            'action' => 'declined',
            'inviter_name' => 'Alice Johnson',
            'group_name' => 'Elite Gaming Squad',
            'url' => route('dashboard'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        
        // Verify the specific message returned by line 170
        $this->assertEquals('Alice Johnson declined to join Elite Gaming Squad', $notifications[0]['message']);
        $this->assertEquals('group_invitation', $notifications[0]['type']);
    }

    public function test_gaming_session_invitation_accepted_message_generation()
    {
        // Test line 174: message generation for gaming_session_invitation with accepted action
        $this->createNotification([
            'type' => 'gaming_session_invitation',
            'action' => 'accepted',
            'host_name' => 'Bob Wilson',
            'game_name' => 'Valorant',
            'url' => route('dashboard'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        
        // Verify the specific message returned by line 174
        $this->assertEquals('Bob Wilson will join your session', $notifications[0]['message']);
        $this->assertEquals('gaming_session_invitation', $notifications[0]['type']);
    }

        public function test_gaming_session_invitation_declined_message_generation()
    {
        // Test line 174: message generation for gaming_session_invitation with accepted action
        $this->createNotification([
            'type' => 'gaming_session_invitation',
            'action' => 'declined',
            'host_name' => 'Bob Wilson',
            'game_name' => 'Valorant',
            'url' => route('dashboard'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        
        // Verify the specific message returned by line 174
        $this->assertEquals('Bob Wilson declined your session', $notifications[0]['message']);
        $this->assertEquals('gaming_session_invitation', $notifications[0]['type']);
    }
}
