<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserConnectionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--seed' => true]);
    }

    public function test_store_creates_connection_request()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        $connectionData = [
            'recipient_id' => $recipient->id,
            'message' => 'Let\'s connect!',
        ];

        $response = $this->actingAs($requester)
            ->post(route('user-connections.store'), $connectionData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Connection request sent successfully!');

        $this->assertDatabaseHas('user_connections', [
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'message' => 'Let\'s connect!',
            'status' => UserConnection::STATUS_PENDING,
        ]);
    }

    public function test_store_prevents_self_connection()
    {
        $user = User::factory()->create();

        $connectionData = [
            'recipient_id' => $user->id,
        ];

        $response = $this->actingAs($user)
            ->post(route('user-connections.store'), $connectionData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['connection' => 'You cannot connect with yourself.']);

        $this->assertDatabaseMissing('user_connections', [
            'requester_id' => $user->id,
            'recipient_id' => $user->id,
        ]);
    }

    public function test_store_prevents_duplicate_pending_request()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        // Create existing pending connection
        UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $connectionData = [
            'recipient_id' => $recipient->id,
        ];

        $response = $this->actingAs($requester)
            ->post(route('user-connections.store'), $connectionData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['connection' => 'A connection request is already pending.']);

        // Should still have only one connection record
        $this->assertEquals(1, UserConnection::where('requester_id', $requester->id)
            ->where('recipient_id', $recipient->id)
            ->count());
    }

    public function test_store_prevents_request_when_already_connected()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        // Create existing accepted connection
        UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        $connectionData = [
            'recipient_id' => $recipient->id,
        ];

        $response = $this->actingAs($requester)
            ->post(route('user-connections.store'), $connectionData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['connection' => 'You are already connected with this user.']);
    }

    public function test_store_prevents_request_when_blocked()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        // Create existing blocked connection
        UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_BLOCKED,
        ]);

        $connectionData = [
            'recipient_id' => $recipient->id,
        ];

        $response = $this->actingAs($requester)
            ->post(route('user-connections.store'), $connectionData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['connection' => 'Connection is not available.']);
    }

    public function test_store_detects_reverse_existing_connection()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create existing connection with user2 as requester
        UserConnection::factory()->create([
            'requester_id' => $user2->id,
            'recipient_id' => $user1->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $connectionData = [
            'recipient_id' => $user2->id,
        ];

        $response = $this->actingAs($user1)
            ->post(route('user-connections.store'), $connectionData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['connection' => 'A connection request is already pending.']);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('user-connections.store'), []);

        $response->assertSessionHasErrors(['recipient_id']);
    }

    public function test_store_validates_recipient_exists()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('user-connections.store'), [
                'recipient_id' => 99999, // Non-existent user
            ]);

        $response->assertSessionHasErrors(['recipient_id']);
    }

    public function test_store_validates_message_length()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($requester)
            ->post(route('user-connections.store'), [
                'recipient_id' => $recipient->id,
                'message' => str_repeat('a', 501), // Too long
            ]);

        $response->assertSessionHasErrors(['message']);
    }

    public function test_store_requires_authentication()
    {
        $recipient = User::factory()->create();

        $response = $this->post(route('user-connections.store'), [
            'recipient_id' => $recipient->id,
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_accept_updates_connection_status()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($recipient)
            ->post(route('user-connections.accept', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Connection request accepted!');

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        $connection->refresh();
        $this->assertNotNull($connection->accepted_at);
    }

    public function test_accept_requires_recipient_authorization()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        $unauthorizedUser = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('user-connections.accept', $connection));

        $response->assertStatus(403);

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);
    }

    public function test_accept_prevents_requester_from_accepting_own_request()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($requester)
            ->post(route('user-connections.accept', $connection));

        $response->assertStatus(403);
    }

    public function test_decline_updates_connection_status()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($recipient)
            ->post(route('user-connections.decline', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Connection request declined.');

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'status' => UserConnection::STATUS_DECLINED,
        ]);
    }

    public function test_decline_requires_recipient_authorization()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        $unauthorizedUser = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('user-connections.decline', $connection));

        $response->assertStatus(403);
    }

    public function test_cancel_deletes_connection()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($requester)
            ->post(route('user-connections.cancel', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Connection request cancelled.');

        $this->assertDatabaseMissing('user_connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_cancel_requires_requester_authorization()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        $unauthorizedUser = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('user-connections.cancel', $connection));

        $response->assertStatus(403);

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_cancel_prevents_recipient_from_cancelling()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($recipient)
            ->post(route('user-connections.cancel', $connection));

        $response->assertStatus(403);
    }

    public function test_block_updates_connection_status()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($user1)
            ->post(route('user-connections.block', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'User blocked.');

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'status' => UserConnection::STATUS_BLOCKED,
        ]);
    }

    public function test_block_works_for_both_parties()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        // User2 can also block
        $response = $this->actingAs($user2)
            ->post(route('user-connections.block', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'User blocked.');

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'status' => UserConnection::STATUS_BLOCKED,
        ]);
    }

    public function test_block_requires_involved_user_authorization()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $unauthorizedUser = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('user-connections.block', $connection));

        $response->assertStatus(403);
    }

    public function test_destroy_deletes_connection()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($user1)
            ->delete(route('user-connections.destroy', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Connection removed.');

        $this->assertDatabaseMissing('user_connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_destroy_works_for_both_parties()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        // User2 can also remove connection
        $response = $this->actingAs($user2)
            ->delete(route('user-connections.destroy', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Connection removed.');

        $this->assertDatabaseMissing('user_connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_destroy_requires_involved_user_authorization()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $unauthorizedUser = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->delete(route('user-connections.destroy', $connection));

        $response->assertStatus(403);

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_all_actions_require_authentication()
    {
        $connection = UserConnection::factory()->create();

        $routes = [
            ['post', 'user-connections.accept'],
            ['post', 'user-connections.decline'],
            ['post', 'user-connections.cancel'],
            ['post', 'user-connections.block'],
            ['delete', 'user-connections.destroy'],
        ];

        foreach ($routes as [$method, $routeName]) {
            $response = $this->$method(route($routeName, $connection));
            $response->assertRedirect(route('login'));
        }
    }

    public function test_connection_workflow_end_to_end()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        // 1. Send connection request
        $response = $this->actingAs($requester)
            ->post(route('user-connections.store'), [
                'recipient_id' => $recipient->id,
                'message' => 'Hello!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $connection = UserConnection::where('requester_id', $requester->id)
            ->where('recipient_id', $recipient->id)
            ->first();

        $this->assertNotNull($connection);
        $this->assertEquals(UserConnection::STATUS_PENDING, $connection->status);

        // 2. Accept connection request
        $response = $this->actingAs($recipient)
            ->post(route('user-connections.accept', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $connection->refresh();
        $this->assertEquals(UserConnection::STATUS_ACCEPTED, $connection->status);
        $this->assertNotNull($connection->accepted_at);

        // 3. Later, remove connection
        $response = $this->actingAs($requester)
            ->delete(route('user-connections.destroy', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('user_connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_decline_workflow()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        // Send connection request
        $this->actingAs($requester)
            ->post(route('user-connections.store'), [
                'recipient_id' => $recipient->id,
            ]);

        $connection = UserConnection::where('requester_id', $requester->id)
            ->where('recipient_id', $recipient->id)
            ->first();

        // Decline connection request
        $response = $this->actingAs($recipient)
            ->post(route('user-connections.decline', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $connection->refresh();
        $this->assertEquals(UserConnection::STATUS_DECLINED, $connection->status);
    }

    public function test_cancel_workflow()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        // Send connection request
        $this->actingAs($requester)
            ->post(route('user-connections.store'), [
                'recipient_id' => $recipient->id,
            ]);

        $connection = UserConnection::where('requester_id', $requester->id)
            ->where('recipient_id', $recipient->id)
            ->first();

        // Cancel connection request
        $response = $this->actingAs($requester)
            ->post(route('user-connections.cancel', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('user_connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_block_workflow()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create accepted connection
        $connection = UserConnection::factory()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        // Block user
        $response = $this->actingAs($user1)
            ->post(route('user-connections.block', $connection));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $connection->refresh();
        $this->assertEquals(UserConnection::STATUS_BLOCKED, $connection->status);
    }
}
