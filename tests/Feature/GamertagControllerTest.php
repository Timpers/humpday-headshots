<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Gamertag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GamertagControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--seed' => true]);
    }

    public function test_index_displays_public_gamertags_paginated()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create public gamertags
        Gamertag::factory()->create([
            'user_id' => $user1->id,
            'gamertag' => 'PublicPlayer1',
            'platform' => 'steam',
            'is_public' => true,
            'is_primary' => true,
        ]);

        Gamertag::factory()->create([
            'user_id' => $user2->id,
            'gamertag' => 'PublicPlayer2',
            'platform' => 'xbox_live',
            'is_public' => true,
            'is_primary' => true,
        ]);

        // Create private gamertag (should not appear)
        Gamertag::factory()->create([
            'user_id' => $user1->id,
            'gamertag' => 'PrivatePlayer',
            'platform' => 'playstation_network', // Different platform to avoid constraint issues
            'is_public' => false,
            'is_primary' => true,
        ]);

        $response = $this->get(route('gamertags.index'));

        $response->assertStatus(200);
        $response->assertViewIs('gamertags.index');
        $response->assertViewHas('gamertags');
        $response->assertSee('PublicPlayer1');
        $response->assertSee('PublicPlayer2');
        $response->assertDontSee('PrivatePlayer');
    }

    public function test_user_gamertags_displays_public_gamertags_for_specific_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create public gamertags for the user
        Gamertag::factory()->create([
            'user_id' => $user->id,
            'gamertag' => 'UserPublic1',
            'platform' => 'steam',
            'is_public' => true,
            'is_primary' => true,
        ]);

        Gamertag::factory()->create([
            'user_id' => $user->id,
            'gamertag' => 'UserPublic2',
            'platform' => 'xbox_live',
            'is_public' => true,
            'is_primary' => true,
        ]);

        // Create private gamertag (should not appear)
        Gamertag::factory()->create([
            'user_id' => $user->id,
            'gamertag' => 'UserPrivate',
            'platform' => 'playstation_network',
            'is_public' => false,
            'is_primary' => true,
        ]);

        // Create public gamertag for other user (should not appear)
        Gamertag::factory()->create([
            'user_id' => $otherUser->id,
            'gamertag' => 'OtherUser',
            'platform' => 'steam',
            'is_public' => true,
            'is_primary' => true,
        ]);

        $response = $this->get(route('gamertags.user', $user));

        $response->assertStatus(200);
        $response->assertViewIs('gamertags.user');
        $response->assertViewHas(['user', 'gamertags']);
        $response->assertSee('UserPublic1');
        $response->assertSee('UserPublic2');
        $response->assertDontSee('UserPrivate');
        $response->assertDontSee('OtherUser');
    }

    public function test_platform_displays_gamertags_for_specific_platform()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create gamertags for Steam platform
        Gamertag::factory()->create([
            'user_id' => $user1->id,
            'gamertag' => 'SteamPlayer1',
            'platform' => 'steam',
            'is_public' => true,
            'is_primary' => true,
        ]);

        Gamertag::factory()->create([
            'user_id' => $user2->id,
            'gamertag' => 'SteamPlayer2',
            'platform' => 'steam',
            'is_public' => true,
            'is_primary' => true,
        ]);

        // Create gamertag for different platform (should not appear)
        Gamertag::factory()->create([
            'user_id' => $user1->id,
            'gamertag' => 'XboxPlayer',
            'platform' => 'xbox_live',
            'is_public' => true,
            'is_primary' => true,
        ]);

        $response = $this->get(route('gamertags.platform', 'steam'));

        $response->assertStatus(200);
        $response->assertViewIs('gamertags.platform');
        $response->assertViewHas(['gamertags', 'platform', 'platformName']);
        $response->assertSee('SteamPlayer1');
        $response->assertSee('SteamPlayer2');
        $response->assertDontSee('XboxPlayer');
    }

    public function test_platform_returns_404_for_invalid_platform()
    {
        $response = $this->get(route('gamertags.platform', 'invalid_platform'));

        $response->assertStatus(404);
    }

    public function test_store_creates_gamertag_for_authenticated_user()
    {
        $user = User::factory()->create();

        $gamertagData = [
            'platform' => 'steam',
            'gamertag' => 'NewGamerTag',
            'display_name' => 'Display Name',
            'is_public' => true,
            'is_primary' => true,
        ];

        $response = $this->actingAs($user)->post(route('gamertags.store'), $gamertagData);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Gamertag added successfully!');

        $this->assertDatabaseHas('gamertags', [
            'user_id' => $user->id,
            'platform' => 'steam',
            'gamertag' => 'NewGamerTag',
            'display_name' => 'Display Name',
            'is_public' => true,
            'is_primary' => true,
        ]);
    }

    public function test_store_handles_checkbox_values_correctly()
    {
        $user = User::factory()->create();

        // Test with checkboxes unchecked (not present in request)
        $gamertagData = [
            'platform' => 'steam',
            'gamertag' => 'NewGamerTag',
        ];

        $response = $this->actingAs($user)->post(route('gamertags.store'), $gamertagData);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('gamertags', [
            'user_id' => $user->id,
            'gamertag' => 'NewGamerTag',
            'is_public' => false,
            'is_primary' => false,
        ]);
    }

    public function test_store_ensures_only_one_primary_gamertag_per_platform()
    {
        $user = User::factory()->create();

        // Create existing primary gamertag
        Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'steam',
            'gamertag' => 'ExistingPrimary',
            'is_primary' => true,
        ]);

        // Create new primary gamertag for same platform
        $gamertagData = [
            'platform' => 'steam',
            'gamertag' => 'NewPrimary',
            'is_primary' => true,
        ];

        $response = $this->actingAs($user)->post(route('gamertags.store'), $gamertagData);

        $response->assertRedirect(route('dashboard'));

        // Old primary should be updated to false
        $this->assertDatabaseHas('gamertags', [
            'user_id' => $user->id,
            'gamertag' => 'ExistingPrimary',
            'is_primary' => false,
        ]);

        // New gamertag should be primary
        $this->assertDatabaseHas('gamertags', [
            'user_id' => $user->id,
            'gamertag' => 'NewPrimary',
            'is_primary' => true,
        ]);
    }

    public function test_store_returns_json_for_ajax_request()
    {
        $user = User::factory()->create();

        $gamertagData = [
            'platform' => 'steam',
            'gamertag' => 'AjaxGamerTag',
            'is_public' => true,
        ];

        $response = $this->actingAs($user)
            ->postJson(route('gamertags.store'), $gamertagData);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Gamertag created successfully!',
        ]);
        $response->assertJsonStructure([
            'message',
            'gamertag' => [
                'id',
                'gamertag',
                'platform',
                'user',
            ],
        ]);
    }

    public function test_store_requires_authentication()
    {
        $gamertagData = [
            'platform' => 'steam',
            'gamertag' => 'UnauthenticatedTag',
        ];

        $response = $this->post(route('gamertags.store'), $gamertagData);

        $response->assertRedirect(route('login'));
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gamertags.store'), []);

        $response->assertSessionHasErrors(['platform', 'gamertag']);
    }

    public function test_store_validates_platform_values()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gamertags.store'), [
            'platform' => 'invalid_platform',
            'gamertag' => 'TestTag',
        ]);

        $response->assertSessionHasErrors(['platform']);
    }

    public function test_update_modifies_existing_gamertag()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'gamertag' => 'OldTag',
            'display_name' => 'Old Display',
        ]);

        $updateData = [
            'gamertag' => 'UpdatedTag',
            'display_name' => 'Updated Display',
            'is_public' => true,
            'is_primary' => true,
        ];

        $response = $this->actingAs($user)
            ->put(route('gamertags.update', $gamertag), $updateData);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Gamertag updated successfully!');

        $this->assertDatabaseHas('gamertags', [
            'id' => $gamertag->id,
            'gamertag' => 'UpdatedTag',
            'display_name' => 'Updated Display',
            'is_public' => true,
            'is_primary' => true,
        ]);
    }

    public function test_update_prevents_unauthorized_user_from_updating()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->put(route('gamertags.update', $gamertag), [
                'gamertag' => 'HackedTag',
            ]);

        $response->assertStatus(403);
    }

    public function test_update_ensures_only_one_primary_per_platform()
    {
        $user = User::factory()->create();

        $existingPrimary = Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'steam',
            'is_primary' => true,
        ]);

        $gamertagToUpdate = Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'steam',
            'is_primary' => false,
        ]);

        $response = $this->actingAs($user)
            ->put(route('gamertags.update', $gamertagToUpdate), [
                'gamertag' => 'UpdatedTag',
                'is_primary' => true,
            ]);

        $response->assertRedirect(route('dashboard'));

        // Existing primary should be updated to false
        $this->assertDatabaseHas('gamertags', [
            'id' => $existingPrimary->id,
            'is_primary' => false,
        ]);

        // Updated gamertag should be primary
        $this->assertDatabaseHas('gamertags', [
            'id' => $gamertagToUpdate->id,
            'is_primary' => true,
        ]);
    }

    public function test_update_returns_json_for_ajax_request()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->putJson(route('gamertags.update', $gamertag), [
                'gamertag' => 'AjaxUpdatedTag',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Gamertag updated successfully!',
        ]);
        $response->assertJsonStructure([
            'message',
            'gamertag',
        ]);
    }

    public function test_destroy_deletes_gamertag()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->delete(route('gamertags.destroy', $gamertag));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Gamertag deleted successfully!');

        $this->assertDatabaseMissing('gamertags', [
            'id' => $gamertag->id,
        ]);
    }

    public function test_destroy_prevents_unauthorized_user_from_deleting()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->delete(route('gamertags.destroy', $gamertag));

        $response->assertStatus(403);

        $this->assertDatabaseHas('gamertags', [
            'id' => $gamertag->id,
        ]);
    }

    public function test_destroy_returns_json_for_ajax_request()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson(route('gamertags.destroy', $gamertag));

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Gamertag deleted successfully!',
        ]);
    }

    public function test_create_displays_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('gamertags.create'));

        $response->assertStatus(200);
        $response->assertViewIs('gamertags.create');
    }

    public function test_create_requires_authentication()
    {
        $response = $this->get(route('gamertags.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_edit_displays_form_with_gamertag()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('gamertags.edit', $gamertag));

        $response->assertStatus(200);
        $response->assertViewIs('gamertags.edit');
        $response->assertViewHas('gamertag', $gamertag);
    }

    public function test_edit_prevents_unauthorized_user_from_accessing()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->get(route('gamertags.edit', $gamertag));

        $response->assertStatus(403);
    }

    public function test_edit_requires_authentication()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user->id]);

        $response = $this->get(route('gamertags.edit', $gamertag));

        $response->assertRedirect(route('login'));
    }

    public function test_validation_enforces_string_max_lengths()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gamertags.store'), [
            'platform' => 'steam',
            'gamertag' => str_repeat('a', 256), // Too long
            'display_name' => str_repeat('b', 256), // Too long
        ]);

        $response->assertSessionHasErrors(['gamertag', 'display_name']);
    }

    public function test_index_orders_by_platform_and_gamertag()
    {
        // Clear existing gamertags to test ordering properly
        \App\Models\Gamertag::query()->delete();
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Create gamertags in specific order to test sorting
        Gamertag::factory()->create([
            'user_id' => $user1->id,
            'platform' => 'xbox_live',
            'gamertag' => 'ZPlayer',
            'is_public' => true,
        ]);

        Gamertag::factory()->create([
            'user_id' => $user2->id,
            'platform' => 'steam',
            'gamertag' => 'BPlayer',
            'is_public' => true,
        ]);

        Gamertag::factory()->create([
            'user_id' => $user3->id,
            'platform' => 'steam',
            'gamertag' => 'APlayer',
            'is_public' => true,
        ]);

        $response = $this->get(route('gamertags.index'));

        $response->assertStatus(200);
        $gamertags = $response->viewData('gamertags');

        // Should be ordered by platform first, then gamertag
        $this->assertEquals('steam', $gamertags[0]->platform);
        $this->assertEquals('APlayer', $gamertags[0]->gamertag);
        $this->assertEquals('steam', $gamertags[1]->platform);
        $this->assertEquals('BPlayer', $gamertags[1]->gamertag);
        $this->assertEquals('xbox_live', $gamertags[2]->platform);
        $this->assertEquals('ZPlayer', $gamertags[2]->gamertag);
    }
}
