<?php

namespace Tests\Unit;

use App\Models\Gamertag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamertagTest extends TestCase
{
    use RefreshDatabase;

    public function test_gamertag_belongs_to_user()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $gamertag->user->id);
        $this->assertEquals($user->name, $gamertag->user->name);
    }

    public function test_gamertag_has_fillable_attributes()
    {
        $fillable = [
            'user_id',
            'platform',
            'gamertag',
            'display_name',
            'is_public',
            'is_primary',
            'additional_data',
        ];

        $gamertag = new Gamertag();
        $this->assertEquals($fillable, $gamertag->getFillable());
    }

    public function test_gamertag_casts_boolean_attributes()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'is_public' => 1,
            'is_primary' => 0,
            'additional_data' => ['level' => 50],
        ]);

        $this->assertIsBool($gamertag->is_public);
        $this->assertIsBool($gamertag->is_primary);
        $this->assertIsArray($gamertag->additional_data);
        $this->assertTrue($gamertag->is_public);
        $this->assertFalse($gamertag->is_primary);
    }

    public function test_platform_name_attribute_returns_display_name()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'steam']);
        $this->assertEquals('Steam', $gamertag->platform_name);

        $gamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'xbox_live']);
        $this->assertEquals('Xbox Live', $gamertag->platform_name);

        $gamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'playstation_network']);
        $this->assertEquals('PlayStation Network', $gamertag->platform_name);

        $gamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'nintendo_online']);
        $this->assertEquals('Nintendo Online', $gamertag->platform_name);

        $gamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'battlenet']);
        $this->assertEquals('Battle.net', $gamertag->platform_name);
    }

    public function test_platform_name_attribute_returns_platform_for_unknown_platform()
    {
        // Since the database has an enum constraint, we can't test with truly unknown platforms
        // Instead, test that the accessor works correctly by manually setting the attribute
        $user = User::factory()->create();
        $gamertag = new Gamertag([
            'user_id' => $user->id,
            'platform' => 'steam', // Use valid platform for creation
            'gamertag' => 'testuser',
            'is_public' => true,
            'is_primary' => false,
        ]);

        // Manually override the platform attribute to test the accessor logic
        $gamertag->setAttribute('platform', 'unknown_platform');
        $this->assertEquals('unknown_platform', $gamertag->platform_name);
    }

    public function test_public_scope_returns_only_public_gamertags()
    {
        $user = User::factory()->create();
        $publicGamertag = Gamertag::factory()->create([
            'user_id' => $user->id, 
            'platform' => 'steam',
            'is_public' => true
        ]);
        $privateGamertag = Gamertag::factory()->create([
            'user_id' => $user->id, 
            'platform' => 'xbox_live',
            'is_public' => false
        ]);

        $publicGamertags = Gamertag::public()->get();

        $this->assertEquals(1, $publicGamertags->count());
        $this->assertTrue($publicGamertags->contains('id', $publicGamertag->id));
        $this->assertFalse($publicGamertags->contains('id', $privateGamertag->id));
    }

    public function test_primary_scope_returns_only_primary_gamertags()
    {
        $user = User::factory()->create();
        $primaryGamertag = Gamertag::factory()->create(['user_id' => $user->id, 'is_primary' => true]);
        $secondaryGamertag = Gamertag::factory()->create(['user_id' => $user->id, 'is_primary' => false]);

        $primaryGamertags = Gamertag::primary()->get();

        $this->assertEquals(1, $primaryGamertags->count());
        $this->assertTrue($primaryGamertags->contains('id', $primaryGamertag->id));
        $this->assertFalse($primaryGamertags->contains('id', $secondaryGamertag->id));
    }

    public function test_platform_scope_filters_by_platform()
    {
        $user = User::factory()->create();
        $steamGamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'steam']);
        $xboxGamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'xbox_live']);
        $playstationGamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'playstation_network']);

        $steamGamertags = Gamertag::platform('steam')->get();
        $xboxGamertags = Gamertag::platform('xbox_live')->get();

        $this->assertEquals(1, $steamGamertags->count());
        $this->assertTrue($steamGamertags->contains('id', $steamGamertag->id));
        $this->assertFalse($steamGamertags->contains('id', $xboxGamertag->id));

        $this->assertEquals(1, $xboxGamertags->count());
        $this->assertTrue($xboxGamertags->contains('id', $xboxGamertag->id));
        $this->assertFalse($xboxGamertags->contains('id', $playstationGamertag->id));
    }

    public function test_profile_url_attribute_returns_correct_urls()
    {
        $user = User::factory()->create();
        $steamGamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'steam',
            'gamertag' => 'testuser123'
        ]);
        $this->assertEquals('https://steamcommunity.com/id/testuser123', $steamGamertag->profile_url);

        $xboxGamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'xbox_live',
            'gamertag' => 'TestGamer'
        ]);
        $this->assertEquals('https://account.xbox.com/en-us/profile?gamertag=TestGamer', $xboxGamertag->profile_url);

        $playstationGamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'playstation_network',
            'gamertag' => 'PSNPlayer'
        ]);
        $this->assertEquals('https://psnprofiles.com/PSNPlayer', $playstationGamertag->profile_url);
    }

    public function test_profile_url_attribute_returns_null_for_platforms_without_public_profiles()
    {
        $user = User::factory()->create();
        $battlenetGamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'battlenet']);
        $this->assertNull($battlenetGamertag->profile_url);

        $nintendoGamertag = Gamertag::factory()->create(['user_id' => $user->id, 'platform' => 'nintendo_online']);
        $this->assertNull($nintendoGamertag->profile_url);

        // Test unknown platform by manually setting the attribute
        $unknownGamertag = new Gamertag([
            'user_id' => $user->id,
            'platform' => 'steam', // Use valid platform for creation
            'gamertag' => 'testuser',
            'is_public' => true,
            'is_primary' => false,
        ]);
        $unknownGamertag->setAttribute('platform', 'unknown_platform');
        $this->assertNull($unknownGamertag->profile_url);
    }

    public function test_gamertag_can_be_created_with_additional_data()
    {
        $user = User::factory()->create();
        $additionalData = [
            'level' => 75,
            'achievements' => 250,
            'join_date' => '2020-01-15'
        ];

        $gamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'additional_data' => $additionalData
        ]);

        $this->assertEquals($additionalData, $gamertag->additional_data);
        $this->assertEquals(75, $gamertag->additional_data['level']);
        $this->assertEquals(250, $gamertag->additional_data['achievements']);
    }

    public function test_gamertag_can_be_created_without_additional_data()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'additional_data' => null
        ]);

        $this->assertNull($gamertag->additional_data);
    }

    public function test_platform_constants_are_defined()
    {
        $expectedPlatforms = [
            'steam' => 'Steam',
            'xbox_live' => 'Xbox Live',
            'playstation_network' => 'PlayStation Network',
            'nintendo_online' => 'Nintendo Online',
            'battlenet' => 'Battle.net',
        ];

        $this->assertEquals($expectedPlatforms, Gamertag::PLATFORMS);
    }

    public function test_gamertag_factory_creates_valid_gamertag()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Gamertag::class, $gamertag);
        $this->assertNotNull($gamertag->platform);
        $this->assertNotNull($gamertag->gamertag);
        $this->assertIsBool($gamertag->is_public);
        $this->assertIsBool($gamertag->is_primary);
        $this->assertContains($gamertag->platform, array_keys(Gamertag::PLATFORMS));
    }

    public function test_gamertag_factory_primary_state()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->primary()->create(['user_id' => $user->id]);

        $this->assertTrue($gamertag->is_primary);
    }

    public function test_gamertag_factory_private_state()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->private()->create(['user_id' => $user->id]);

        $this->assertFalse($gamertag->is_public);
    }

    public function test_gamertag_factory_platform_state()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->platform('steam')->create(['user_id' => $user->id]);

        $this->assertEquals('steam', $gamertag->platform);
    }

    public function test_multiple_scopes_can_be_chained()
    {
        $user = User::factory()->create();

        $targetGamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'steam',
            'is_public' => true,
            'is_primary' => true,
        ]);

        $otherGamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'steam',
            'is_public' => false,
            'is_primary' => false,
        ]);

        $results = Gamertag::where('user_id', $user->id)
            ->platform('steam')
            ->public()
            ->primary()
            ->get();

        $this->assertEquals(1, $results->count());
        $this->assertTrue($results->contains('id', $targetGamertag->id));
        $this->assertFalse($results->contains('id', $otherGamertag->id));
    }
}
