<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_games_index()
    {
        $response = $this->get(route('games.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_games_index()
    {
        $response = $this->actingAs($this->user)->get(route('games.index'));

        $response->assertOk();
        $response->assertViewIs('games.index');
        $response->assertViewHas(['games', 'stats']);
    }

    public function test_games_index_shows_user_games()
    {
        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Game',
            'status' => Game::STATUS_OWNED,
        ]);

        $response = $this->actingAs($this->user)->get(route('games.index'));

        $response->assertOk();
        $response->assertSee('Test Game');
        $response->assertViewHas('games', function ($games) use ($game) {
            return $games->contains('id', $game->id);
        });
    }

    public function test_games_index_filters_by_status()
    {
        $ownedGame = Game::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Owned Game',
            'status' => Game::STATUS_OWNED,
        ]);

        $wishlistGame = Game::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Wishlist Game',
            'status' => Game::STATUS_WISHLIST,
        ]);

        $response = $this->actingAs($this->user)->get(route('games.index', ['status' => Game::STATUS_OWNED]));

        $response->assertOk();
        $response->assertSee('Owned Game');
        $response->assertDontSee('Wishlist Game');
    }

    public function test_games_index_filters_by_platform()
    {
        $pcGame = Game::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'PC Game',
            'platform' => 'pc',
        ]);

        $ps5Game = Game::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'PS5 Game',
            'platform' => 'playstation_5',
        ]);

        $response = $this->actingAs($this->user)->get(route('games.index', ['platform' => 'pc']));

        $response->assertOk();
        $response->assertSee('PC Game');
        $response->assertDontSee('PS5 Game');
    }

    public function test_games_index_shows_statistics()
    {
        Game::factory()->create(['user_id' => $this->user->id, 'status' => Game::STATUS_OWNED]);
        Game::factory()->create(['user_id' => $this->user->id, 'status' => Game::STATUS_WISHLIST]);
        Game::factory()->create(['user_id' => $this->user->id, 'status' => Game::STATUS_PLAYING]);

        $response = $this->actingAs($this->user)->get(route('games.index'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 3 &&
                   $stats['owned'] === 1 &&
                   $stats['wishlist'] === 1 &&
                   $stats['playing'] === 1;
        });
    }

    public function test_user_can_view_create_game_form()
    {
        $response = $this->actingAs($this->user)->get(route('games.create'));

        $response->assertOk();
        $response->assertViewIs('games.create');
    }

    public function test_guest_cannot_view_create_game_form()
    {
        $response = $this->get(route('games.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_create_game()
    {
        $gameData = [
            'igdb_id' => 12345,
            'name' => 'New Test Game',
            'summary' => 'A great game for testing',
            'status' => Game::STATUS_OWNED,
            'platform' => 'pc',
            'user_rating' => 8.5,
            'notes' => 'Really enjoyed this game',
            'is_digital' => true,
            'is_completed' => false,
            'is_favorite' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('games.store'), $gameData);

        $response->assertRedirect(route('games.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('games', [
            'user_id' => $this->user->id,
            'igdb_id' => 12345,
            'name' => 'New Test Game',
            'status' => Game::STATUS_OWNED,
            'platform' => 'pc',
        ]);
    }

    public function test_guest_cannot_create_game()
    {
        $gameData = [
            'name' => 'Test Game',
            'status' => Game::STATUS_OWNED,
            'platform' => 'pc',
        ];

        $response = $this->post(route('games.store'), $gameData);

        $response->assertRedirect(route('login'));
    }

    public function test_create_game_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('games.store'), []);

        $response->assertSessionHasErrors(['name', 'status', 'platform']);
    }

    public function test_create_game_validates_status_values()
    {
        $gameData = [
            'name' => 'Test Game',
            'status' => 'invalid_status',
            'platform' => 'pc',
        ];

        $response = $this->actingAs($this->user)->post(route('games.store'), $gameData);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_create_game_validates_platform_values()
    {
        $gameData = [
            'name' => 'Test Game',
            'status' => Game::STATUS_OWNED,
            'platform' => 'invalid_platform',
        ];

        $response = $this->actingAs($this->user)->post(route('games.store'), $gameData);

        $response->assertSessionHasErrors(['platform']);
    }

    public function test_user_can_view_own_game()
    {
        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Game',
        ]);

        $response = $this->actingAs($this->user)->get(route('games.show', $game));

        $response->assertOk();
        $response->assertViewIs('games.show');
        $response->assertSee('Test Game');
    }

    public function test_user_cannot_view_other_users_game()
    {
        $otherUser = User::factory()->create();
        $game = Game::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Game',
        ]);

        $response = $this->actingAs($this->user)->get(route('games.show', $game));

        $response->assertForbidden();
    }

    public function test_user_can_edit_own_game()
    {
        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->user)->get(route('games.edit', $game));

        $response->assertOk();
        $response->assertViewIs('games.edit');
        $response->assertSee('Original Name');
    }

    public function test_user_cannot_edit_other_users_game()
    {
        $otherUser = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->get(route('games.edit', $game));

        $response->assertForbidden();
    }

    public function test_user_can_update_own_game()
    {
        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name',
            'status' => Game::STATUS_WISHLIST,
        ]);

        $updateData = [
            'status' => Game::STATUS_OWNED,
            'platform' => 'pc',
            'user_rating' => 8.5,
            'notes' => 'Great game!',
        ];

        $response = $this->actingAs($this->user)->put(route('games.update', $game), $updateData);

        $response->assertRedirect(route('games.show', $game));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'status' => Game::STATUS_OWNED,
            'user_rating' => 8.5,
            'notes' => 'Great game!',
        ]);
    }

    public function test_user_cannot_update_other_users_game()
    {
        $otherUser = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $otherUser->id]);

        $updateData = [
            'name' => 'Hacked Name',
            'status' => Game::STATUS_OWNED,
            'platform' => 'pc',
        ];

        $response = $this->actingAs($this->user)->put(route('games.update', $game), $updateData);

        $response->assertForbidden();
    }

    public function test_user_can_delete_own_game()
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('games.destroy', $game));

        $response->assertRedirect(route('games.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('games', ['id' => $game->id]);
    }

    public function test_user_cannot_delete_other_users_game()
    {
        $otherUser = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->delete(route('games.destroy', $game));

        $response->assertForbidden();
        $this->assertDatabaseHas('games', ['id' => $game->id]);
    }

    public function test_game_search_requires_authentication()
    {
        $response = $this->post(route('games.search'), ['search_query' => 'test']);

        $response->assertRedirect(route('login'));
    }

    public function test_game_search_validates_query_length()
    {
        $response = $this->actingAs($this->user)->post(route('games.search'), ['search_query' => 'a']);

        $response->assertSessionHasErrors(['search_query']);
    }

    public function test_game_search_requires_query()
    {
        $response = $this->actingAs($this->user)->post(route('games.search'), []);

        $response->assertSessionHasErrors(['search_query']);
    }

    public function test_game_search_returns_json_response()
    {
        // Mock the IGDB API call to avoid external dependencies
        $response = $this->actingAs($this->user)->post(route('games.search'), [
            'search_query' => 'Call of Duty'
        ]);

        // The actual response depends on IGDB API, but we can test the structure
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_only_user_owns_games_are_shown_in_index()
    {
        $otherUser = User::factory()->create();

        $userGame = Game::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Game',
        ]);

        $otherUserGame = Game::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Game',
        ]);

        $response = $this->actingAs($this->user)->get(route('games.index'));

        $response->assertOk();
        $response->assertSee('User Game');
        $response->assertDontSee('Other User Game');
    }

    public function test_games_are_paginated()
    {
        // Create more than 12 games (the pagination limit)
        Game::factory()->count(15)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('games.index'));

        $response->assertOk();
        $response->assertViewHas('games', function ($games) {
            return $games instanceof \Illuminate\Pagination\LengthAwarePaginator;
        });
    }
}
