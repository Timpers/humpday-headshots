<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_belongs_to_user()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $game->user->id);
        $this->assertEquals($user->name, $game->user->name);
    }

    public function test_game_has_fillable_attributes()
    {
        $fillable = [
            'user_id',
            'igdb_id',
            'name',
            'summary',
            'slug',
            'cover',
            'screenshots',
            'release_date',
            'genres',
            'platforms',
            'rating',
            'status',
            'platform',
            'user_rating',
            'notes',
            'hours_played',
            'date_purchased',
            'price_paid',
            'is_digital',
            'is_completed',
            'is_favorite',
        ];

        $game = new Game();
        $this->assertEquals($fillable, $game->getFillable());
    }

    public function test_game_casts_attributes()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'user_id' => $user->id,
            'cover' => ['url' => '//example.com/image.jpg'],
            'screenshots' => [['url' => '//example.com/screenshot1.jpg']],
            'genres' => [['name' => 'Action'], ['name' => 'RPG']],
            'platforms' => [['name' => 'PC'], ['name' => 'PlayStation 5']],
            'release_date' => '2023-01-15',
            'date_purchased' => '2023-02-01',
            'rating' => 8.5,
            'user_rating' => 9.0,
            'price_paid' => 59.99,
            'is_digital' => true,
            'is_completed' => false,
            'is_favorite' => true,
        ]);

        $this->assertIsArray($game->cover);
        $this->assertIsArray($game->screenshots);
        $this->assertIsArray($game->genres);
        $this->assertIsArray($game->platforms);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $game->release_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $game->date_purchased);
        $this->assertEquals(8.5, $game->rating);
        $this->assertEquals(9.0, $game->user_rating);
        $this->assertEquals(59.99, $game->price_paid);
        $this->assertIsBool($game->is_digital);
        $this->assertIsBool($game->is_completed);
        $this->assertIsBool($game->is_favorite);
        $this->assertTrue($game->is_digital);
        $this->assertFalse($game->is_completed);
        $this->assertTrue($game->is_favorite);
    }

    public function test_status_constants_are_defined()
    {
        $expectedStatuses = [
            'owned' => 'Owned',
            'wishlist' => 'Wishlist',
            'playing' => 'Currently Playing',
            'completed' => 'Completed',
        ];

        $this->assertEquals('owned', Game::STATUS_OWNED);
        $this->assertEquals('wishlist', Game::STATUS_WISHLIST);
        $this->assertEquals('playing', Game::STATUS_PLAYING);
        $this->assertEquals('completed', Game::STATUS_COMPLETED);
        $this->assertEquals($expectedStatuses, Game::STATUSES);
    }

    public function test_platform_constants_are_defined()
    {
        $expectedPlatforms = [
            'pc' => 'PC',
            'playstation_5' => 'PlayStation 5',
            'playstation_4' => 'PlayStation 4',
            'xbox_series' => 'Xbox Series X/S',
            'xbox_one' => 'Xbox One',
            'nintendo_switch' => 'Nintendo Switch',
            'steam' => 'Steam',
            'epic_games' => 'Epic Games Store',
            'mobile' => 'Mobile',
            'other' => 'Other',
        ];

        $this->assertEquals($expectedPlatforms, Game::PLATFORMS);
    }

    public function test_by_status_scope()
    {
        $user = User::factory()->create();
        $ownedGame = Game::factory()->create(['user_id' => $user->id, 'status' => Game::STATUS_OWNED]);
        $wishlistGame = Game::factory()->create(['user_id' => $user->id, 'status' => Game::STATUS_WISHLIST]);
        $playingGame = Game::factory()->create(['user_id' => $user->id, 'status' => Game::STATUS_PLAYING]);

        $ownedGames = Game::byStatus(Game::STATUS_OWNED)->get();
        $wishlistGames = Game::byStatus(Game::STATUS_WISHLIST)->get();

        $this->assertEquals(1, $ownedGames->count());
        $this->assertTrue($ownedGames->contains('id', $ownedGame->id));
        $this->assertFalse($ownedGames->contains('id', $wishlistGame->id));

        $this->assertEquals(1, $wishlistGames->count());
        $this->assertTrue($wishlistGames->contains('id', $wishlistGame->id));
        $this->assertFalse($wishlistGames->contains('id', $playingGame->id));
    }

    public function test_by_platform_scope()
    {
        $user = User::factory()->create();
        $pcGame = Game::factory()->create(['user_id' => $user->id, 'platform' => 'pc']);
        $ps5Game = Game::factory()->create(['user_id' => $user->id, 'platform' => 'playstation_5']);
        $xboxGame = Game::factory()->create(['user_id' => $user->id, 'platform' => 'xbox_series']);

        $pcGames = Game::byPlatform('pc')->get();
        $ps5Games = Game::byPlatform('playstation_5')->get();

        $this->assertEquals(1, $pcGames->count());
        $this->assertTrue($pcGames->contains('id', $pcGame->id));
        $this->assertFalse($pcGames->contains('id', $ps5Game->id));

        $this->assertEquals(1, $ps5Games->count());
        $this->assertTrue($ps5Games->contains('id', $ps5Game->id));
        $this->assertFalse($ps5Games->contains('id', $xboxGame->id));
    }

    public function test_favorites_scope()
    {
        $user = User::factory()->create();
        $favoriteGame = Game::factory()->create(['user_id' => $user->id, 'is_favorite' => true]);
        $regularGame = Game::factory()->create(['user_id' => $user->id, 'is_favorite' => false]);

        $favoriteGames = Game::favorites()->get();

        $this->assertEquals(1, $favoriteGames->count());
        $this->assertTrue($favoriteGames->contains('id', $favoriteGame->id));
        $this->assertFalse($favoriteGames->contains('id', $regularGame->id));
    }

    public function test_completed_scope()
    {
        $user = User::factory()->create();
        $completedGame = Game::factory()->create(['user_id' => $user->id, 'is_completed' => true]);
        $uncompletedGame = Game::factory()->create(['user_id' => $user->id, 'is_completed' => false]);

        $completedGames = Game::completed()->get();

        $this->assertEquals(1, $completedGames->count());
        $this->assertTrue($completedGames->contains('id', $completedGame->id));
        $this->assertFalse($completedGames->contains('id', $uncompletedGame->id));
    }

    public function test_formatted_status_attribute()
    {
        $user = User::factory()->create();

        $ownedGame = Game::factory()->create(['user_id' => $user->id, 'status' => Game::STATUS_OWNED]);
        $this->assertEquals('Owned', $ownedGame->formatted_status);

        $wishlistGame = Game::factory()->create(['user_id' => $user->id, 'status' => Game::STATUS_WISHLIST]);
        $this->assertEquals('Wishlist', $wishlistGame->formatted_status);

        $playingGame = Game::factory()->create(['user_id' => $user->id, 'status' => Game::STATUS_PLAYING]);
        $this->assertEquals('Currently Playing', $playingGame->formatted_status);

        $completedGame = Game::factory()->create(['user_id' => $user->id, 'status' => Game::STATUS_COMPLETED]);
        $this->assertEquals('Completed', $completedGame->formatted_status);
    }

    public function test_formatted_status_attribute_with_unknown_status()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id, 'status' => 'unknown_status']);

        $this->assertEquals('Unknown_status', $game->formatted_status);
    }

    public function test_formatted_platform_attribute()
    {
        $user = User::factory()->create();

        $pcGame = Game::factory()->create(['user_id' => $user->id, 'platform' => 'pc']);
        $this->assertEquals('PC', $pcGame->formatted_platform);

        $ps5Game = Game::factory()->create(['user_id' => $user->id, 'platform' => 'playstation_5']);
        $this->assertEquals('PlayStation 5', $ps5Game->formatted_platform);

        $xboxGame = Game::factory()->create(['user_id' => $user->id, 'platform' => 'xbox_series']);
        $this->assertEquals('Xbox Series X/S', $xboxGame->formatted_platform);
    }

    public function test_formatted_platform_attribute_with_unknown_platform()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id, 'platform' => 'unknown_platform']);

        $this->assertEquals('Unknown_platform', $game->formatted_platform);
    }

    public function test_cover_url_attribute()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'user_id' => $user->id,
            'cover' => ['url' => '//images.igdb.com/igdb/image/upload/t_thumb/abcd.jpg']
        ]);

        $expectedUrl = 'https://images.igdb.com/igdb/image/upload/t_cover_big/abcd.jpg';
        $this->assertEquals($expectedUrl, $game->cover_url);
    }

    public function test_cover_url_attribute_returns_null_when_no_cover()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id, 'cover' => null]);

        $this->assertNull($game->cover_url);
    }

    public function test_cover_thumbnail_attribute()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'user_id' => $user->id,
            'cover' => ['url' => '//images.igdb.com/igdb/image/upload/t_thumb/abcd.jpg']
        ]);

        $expectedUrl = 'https://images.igdb.com/igdb/image/upload/t_thumb/abcd.jpg';
        $this->assertEquals($expectedUrl, $game->cover_thumbnail);
    }

    public function test_cover_thumbnail_attribute_returns_null_when_no_cover()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id, 'cover' => null]);

        $this->assertNull($game->cover_thumbnail);
    }

    public function test_formatted_genres_attribute()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'user_id' => $user->id,
            'genres' => [
                ['name' => 'Action'],
                ['name' => 'RPG'],
                ['name' => 'Adventure']
            ]
        ]);

        $this->assertEquals('Action, RPG, Adventure', $game->formatted_genres);
    }

    public function test_formatted_genres_attribute_returns_empty_when_no_genres()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id, 'genres' => null]);

        $this->assertEquals('', $game->formatted_genres);
    }

    public function test_formatted_genres_attribute_returns_empty_when_empty_array()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id, 'genres' => []]);

        $this->assertEquals('', $game->formatted_genres);
    }

    public function test_game_factory_creates_valid_game()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals($user->id, $game->user_id);
        $this->assertNotNull($game->name);
        $this->assertContains($game->status, array_keys(Game::STATUSES));
        $this->assertContains($game->platform, array_keys(Game::PLATFORMS));
    }

    public function test_multiple_scopes_can_be_chained()
    {
        $user = User::factory()->create();

        $targetGame = Game::factory()->create([
            'user_id' => $user->id,
            'status' => Game::STATUS_OWNED,
            'platform' => 'pc',
            'is_favorite' => true,
            'is_completed' => true,
        ]);

        $otherGame = Game::factory()->create([
            'user_id' => $user->id,
            'status' => Game::STATUS_WISHLIST,
            'platform' => 'pc',
            'is_favorite' => false,
            'is_completed' => false,
        ]);

        $results = Game::where('user_id', $user->id)
            ->byStatus(Game::STATUS_OWNED)
            ->byPlatform('pc')
            ->favorites()
            ->completed()
            ->get();

        $this->assertEquals(1, $results->count());
        $this->assertTrue($results->contains('id', $targetGame->id));
        $this->assertFalse($results->contains('id', $otherGame->id));
    }

    public function test_game_can_be_created_with_complete_data()
    {
        $user = User::factory()->create();
        $gameData = [
            'user_id' => $user->id,
            'igdb_id' => 12345,
            'name' => 'Test Game',
            'summary' => 'A test game for unit testing',
            'slug' => 'test-game',
            'cover' => ['url' => '//example.com/cover.jpg'],
            'screenshots' => [['url' => '//example.com/screenshot1.jpg']],
            'release_date' => '2023-01-15',
            'genres' => [['name' => 'Action'], ['name' => 'RPG']],
            'platforms' => [['name' => 'PC']],
            'rating' => 8.5,
            'status' => Game::STATUS_OWNED,
            'platform' => 'pc',
            'user_rating' => 9.0,
            'notes' => 'Great game!',
            'hours_played' => 50,
            'date_purchased' => '2023-02-01',
            'price_paid' => 59.99,
            'is_digital' => true,
            'is_completed' => true,
            'is_favorite' => true,
        ];

        $game = Game::create($gameData);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals('Test Game', $game->name);
        $this->assertEquals(12345, $game->igdb_id);
        $this->assertEquals(Game::STATUS_OWNED, $game->status);
        $this->assertEquals('pc', $game->platform);
        $this->assertTrue($game->is_digital);
        $this->assertTrue($game->is_completed);
        $this->assertTrue($game->is_favorite);
        $this->assertEquals(50, $game->hours_played);
        $this->assertEquals(59.99, $game->price_paid);
    }
}
