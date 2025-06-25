<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_connection_belongs_to_requester()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
        ]);

        $this->assertEquals($requester->id, $connection->requester->id);
        $this->assertEquals($requester->name, $connection->requester->name);
    }

    public function test_user_connection_belongs_to_recipient()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
        ]);

        $this->assertEquals($recipient->id, $connection->recipient->id);
        $this->assertEquals($recipient->name, $connection->recipient->name);
    }

    public function test_user_connection_has_fillable_attributes()
    {
        $fillable = [
            'requester_id',
            'recipient_id',
            'status',
            'message',
            'accepted_at',
        ];

        $connection = new UserConnection();
        $this->assertEquals($fillable, $connection->getFillable());
    }

    public function test_user_connection_casts_attributes()
    {
        $connection = UserConnection::factory()->accepted()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $connection->accepted_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $connection->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $connection->updated_at);
    }

    public function test_status_constants_are_defined()
    {
        $expectedStatuses = [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'blocked' => 'Blocked',
        ];

        $this->assertEquals('pending', UserConnection::STATUS_PENDING);
        $this->assertEquals('accepted', UserConnection::STATUS_ACCEPTED);
        $this->assertEquals('declined', UserConnection::STATUS_DECLINED);
        $this->assertEquals('blocked', UserConnection::STATUS_BLOCKED);
        $this->assertEquals($expectedStatuses, UserConnection::STATUSES);
    }

    public function test_by_status_scope()
    {
        $pendingConnection = UserConnection::factory()->pending()->create();
        $acceptedConnection = UserConnection::factory()->accepted()->create();
        $declinedConnection = UserConnection::factory()->declined()->create();

        $pendingConnections = UserConnection::byStatus(UserConnection::STATUS_PENDING)->get();
        $acceptedConnections = UserConnection::byStatus(UserConnection::STATUS_ACCEPTED)->get();

        $this->assertEquals(1, $pendingConnections->count());
        $this->assertTrue($pendingConnections->contains('id', $pendingConnection->id));
        $this->assertFalse($pendingConnections->contains('id', $acceptedConnection->id));

        $this->assertEquals(1, $acceptedConnections->count());
        $this->assertTrue($acceptedConnections->contains('id', $acceptedConnection->id));
        $this->assertFalse($acceptedConnections->contains('id', $declinedConnection->id));
    }

    public function test_pending_scope()
    {
        $pendingConnection = UserConnection::factory()->pending()->create();
        $acceptedConnection = UserConnection::factory()->accepted()->create();

        $pendingConnections = UserConnection::pending()->get();

        $this->assertEquals(1, $pendingConnections->count());
        $this->assertTrue($pendingConnections->contains('id', $pendingConnection->id));
        $this->assertFalse($pendingConnections->contains('id', $acceptedConnection->id));
    }

    public function test_accepted_scope()
    {
        $pendingConnection = UserConnection::factory()->pending()->create();
        $acceptedConnection = UserConnection::factory()->accepted()->create();

        $acceptedConnections = UserConnection::accepted()->get();

        $this->assertEquals(1, $acceptedConnections->count());
        $this->assertTrue($acceptedConnections->contains('id', $acceptedConnection->id));
        $this->assertFalse($acceptedConnections->contains('id', $pendingConnection->id));
    }

    public function test_get_other_user_method()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
        ]);

        $otherUserFromRequester = $connection->getOtherUser($requester->id);
        $otherUserFromRecipient = $connection->getOtherUser($recipient->id);

        $this->assertEquals($recipient->id, $otherUserFromRequester->id);
        $this->assertEquals($requester->id, $otherUserFromRecipient->id);
    }

    public function test_is_accepted_method()
    {
        $acceptedConnection = UserConnection::factory()->accepted()->create();
        $pendingConnection = UserConnection::factory()->pending()->create();

        $this->assertTrue($acceptedConnection->isAccepted());
        $this->assertFalse($pendingConnection->isAccepted());
    }

    public function test_is_pending_method()
    {
        $pendingConnection = UserConnection::factory()->pending()->create();
        $acceptedConnection = UserConnection::factory()->accepted()->create();

        $this->assertTrue($pendingConnection->isPending());
        $this->assertFalse($acceptedConnection->isPending());
    }

    public function test_accept_method()
    {
        $connection = UserConnection::factory()->pending()->create([
            'accepted_at' => null,
        ]);

        $connection->accept();

        $this->assertEquals(UserConnection::STATUS_ACCEPTED, $connection->status);
        $this->assertNotNull($connection->accepted_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $connection->accepted_at);
    }

    public function test_decline_method()
    {
        $connection = UserConnection::factory()->pending()->create([
            'accepted_at' => null,
        ]);

        $connection->decline();

        $this->assertEquals(UserConnection::STATUS_DECLINED, $connection->status);
        $this->assertNull($connection->accepted_at);
    }

    public function test_user_connection_factory_creates_valid_connection()
    {
        $connection = UserConnection::factory()->create();

        $this->assertInstanceOf(UserConnection::class, $connection);
        $this->assertNotNull($connection->requester_id);
        $this->assertNotNull($connection->recipient_id);
        $this->assertNotEquals($connection->requester_id, $connection->recipient_id);
        $this->assertContains($connection->status, [
            UserConnection::STATUS_PENDING,
            UserConnection::STATUS_ACCEPTED,
            UserConnection::STATUS_DECLINED,
            UserConnection::STATUS_BLOCKED
        ]);
    }

    public function test_user_connection_can_be_created_with_complete_data()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        $connectionData = [
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
            'message' => 'Let\'s connect!',
            'accepted_at' => null,
        ];

        $connection = UserConnection::create($connectionData);

        $this->assertInstanceOf(UserConnection::class, $connection);
        $this->assertEquals($requester->id, $connection->requester_id);
        $this->assertEquals($recipient->id, $connection->recipient_id);
        $this->assertEquals(UserConnection::STATUS_PENDING, $connection->status);
        $this->assertEquals('Let\'s connect!', $connection->message);
        $this->assertNull($connection->accepted_at);
    }

    public function test_accept_updates_database()
    {
        $connection = UserConnection::factory()->pending()->create();

        $connection->accept();

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        $this->assertDatabaseMissing('user_connections', [
            'id' => $connection->id,
            'accepted_at' => null,
        ]);
    }

    public function test_decline_updates_database()
    {
        $connection = UserConnection::factory()->pending()->create();

        $connection->decline();

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'status' => UserConnection::STATUS_DECLINED,
        ]);
    }

    public function test_multiple_scopes_can_be_chained()
    {
        $user = User::factory()->create();

        $targetConnection = UserConnection::factory()->create([
            'requester_id' => $user->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $otherConnection = UserConnection::factory()->create([
            'recipient_id' => $user->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        $results = UserConnection::pending()
            ->where('requester_id', $user->id)
            ->get();

        $this->assertEquals(1, $results->count());
        $this->assertTrue($results->contains('id', $targetConnection->id));
        $this->assertFalse($results->contains('id', $otherConnection->id));
    }
}
