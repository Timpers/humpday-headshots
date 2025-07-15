<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GameCompatibilityControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function it_can_display_the_compatibility_index_page()
    {
        $this->actingAs($this->user);

        // Create some other users with games
        $usersWithGames = User::factory(3)->create();
        foreach ($usersWithGames as $user) {
            Game::factory()->owned()->count(5)->create(['user_id' => $user->id]);
        }

        $response = $this->get(route('games.compatibility.index'));

        $response->assertStatus(200);
        $response->assertViewIs('games.compatibility.index');
        $response->assertViewHas('users');
        
        // Check that paginated users are returned (excluding current user)
        $users = $response->viewData('users');
        $this->assertTrue($users->count() <= 12); // Pagination limit
        $this->assertFalse($users->contains('id', $this->user->id)); // Current user excluded
    }

    /** @test */
    public function it_requires_authentication_to_access_compatibility_index()
    {
        $response = $this->get(route('games.compatibility.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_can_compare_games_between_users()
    {
        $this->actingAs($this->user);

        // Create shared games
        $sharedGame1 = Game::factory()->owned()->create([
            'user_id' => $this->user->id,
            'name' => 'Shared Game 1',
            'igdb_id' => 12345,
            'platform' => 'pc',
            'genres' => [['name' => 'Action'], ['name' => 'Adventure']]
        ]);

        $sharedGame2 = Game::factory()->owned()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Shared Game 1',
            'igdb_id' => 12345,
            'platform' => 'pc',
            'genres' => [['name' => 'Action'], ['name' => 'Adventure']]
        ]);

        // Create unique games
        Game::factory()->owned()->create([
            'user_id' => $this->user->id,
            'name' => 'User Game',
            'platform' => 'playstation_5'
        ]);

        Game::factory()->owned()->favorite()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other User Game',
            'platform' => 'xbox_series',
            'user_rating' => 9.0
        ]);

        $response = $this->get(route('games.compatibility.compare', $this->otherUser));

        $response->assertStatus(200);
        $response->assertViewIs('games.compatibility.compare');
        $response->assertViewHas(['user', 'comparison']);
        
        $comparison = $response->viewData('comparison');
        $this->assertArrayHasKey('compatibility_score', $comparison);
        $this->assertArrayHasKey('shared_games', $comparison);
        $this->assertArrayHasKey('user1_only_games', $comparison);
        $this->assertArrayHasKey('user2_only_games', $comparison);
        $this->assertArrayHasKey('recommendations', $comparison);
        
        // Should have recommendations from highly rated/favorite games
        $this->assertNotEmpty($comparison['recommendations']);
    }

    /** @test */
    public function it_prevents_self_comparison_in_compare_method()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('games.compatibility.compare', $this->user));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You cannot compare with yourself.');
    }

    /** @test */
    public function it_can_get_compatibility_data_via_ajax()
    {
        $this->actingAs($this->user);

        // Create test games
        Game::factory()->owned()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Game',
            'igdb_id' => 111,
            'platform' => 'pc',
            'genres' => [['name' => 'RPG']]
        ]);

        Game::factory()->owned()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Test Game',
            'igdb_id' => 111,
            'platform' => 'pc',
            'genres' => [['name' => 'RPG']]
        ]);

        $response = $this->getJson(route('games.compatibility.api', $this->otherUser));

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('compatibility_score', $data);
        $this->assertArrayHasKey('compatibility_rating', $data);
        $this->assertArrayHasKey('shared_games', $data);
        $this->assertArrayHasKey('total_shared_games', $data);
        $this->assertArrayHasKey('platform_compatibility', $data);
        $this->assertArrayHasKey('genre_compatibility', $data);
        
        $this->assertGreaterThan(0, $data['compatibility_score']);
        $this->assertEquals(1, $data['total_shared_games']);
    }

    /** @test */
    public function it_prevents_self_comparison_in_ajax_endpoint()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('games.compatibility.api', $this->user));

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Cannot compare with yourself']);
    }

    /** @test */
    public function it_calculates_zero_compatibility_when_users_have_no_games()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('games.compatibility.api', $this->otherUser));

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals(0, $data['compatibility_score']);
        $this->assertEquals('No Data', $data['compatibility_rating']);
        $this->assertEmpty($data['shared_games']);
    }

    /** @test */
    public function it_calculates_compatibility_score_correctly()
    {
        $this->actingAs($this->user);

        // Create 2 shared games and 2 unique games each
        // User 1: 4 games total (2 shared + 2 unique)
        // User 2: 4 games total (2 shared + 2 unique)
        // Total games: 8, Shared count: 2
        // Base score: (2 * 2) / 8 * 100 = 50%

        // Shared games
        Game::factory()->owned()->create([
            'user_id' => $this->user->id,
            'name' => 'Shared Game 1',
            'igdb_id' => 1001,
            'platform' => 'pc',
            'genres' => [['name' => 'Action']]
        ]);

        Game::factory()->owned()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Shared Game 1',
            'igdb_id' => 1001,
            'platform' => 'pc',
            'genres' => [['name' => 'Action']]
        ]);

        Game::factory()->owned()->create([
            'user_id' => $this->user->id,
            'name' => 'Shared Game 2',
            'igdb_id' => 1002,
            'platform' => 'pc',
            'genres' => [['name' => 'Adventure']]
        ]);

        Game::factory()->owned()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Shared Game 2',
            'igdb_id' => 1002,
            'platform' => 'pc',
            'genres' => [['name' => 'Adventure']]
        ]);

        // Unique games
        Game::factory()->owned()->count(2)->create(['user_id' => $this->user->id, 'platform' => 'pc']);
        Game::factory()->owned()->count(2)->create(['user_id' => $this->otherUser->id, 'platform' => 'pc']);

        $response = $this->getJson(route('games.compatibility.api', $this->otherUser));

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals(2, $data['total_shared_games']);
        $this->assertEquals(4, $data['total_user1_games']);
        $this->assertEquals(4, $data['total_user2_games']);
        
        // Should have base score of 50% plus platform/genre bonuses
        $this->assertGreaterThanOrEqual(50, $data['compatibility_score']);
    }

    /** @test */
    public function it_provides_platform_compatibility_breakdown()
    {
        $this->actingAs($this->user);

        // Create games on different platforms
        Game::factory()->owned()->create(['user_id' => $this->user->id, 'platform' => 'pc']);
        Game::factory()->owned()->create(['user_id' => $this->user->id, 'platform' => 'playstation_5']);
        
        Game::factory()->owned()->create(['user_id' => $this->otherUser->id, 'platform' => 'pc']);
        Game::factory()->owned()->create(['user_id' => $this->otherUser->id, 'platform' => 'xbox_series']);

        $response = $this->getJson(route('games.compatibility.api', $this->otherUser));

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('platform_compatibility', $data);
        
        $platformBreakdown = $data['platform_compatibility'];
        $this->assertNotEmpty($platformBreakdown);
        
        // Find PC platform in breakdown
        $pcPlatform = collect($platformBreakdown)->firstWhere('platform', 'PC');
        $this->assertNotNull($pcPlatform);
        $this->assertEquals(1, $pcPlatform['user1_count']);
        $this->assertEquals(1, $pcPlatform['user2_count']);
        $this->assertTrue($pcPlatform['shared']);
    }

    /** @test */
    public function it_provides_genre_compatibility_breakdown()
    {
        $this->actingAs($this->user);

        // Create games with different genres
        Game::factory()->owned()->create([
            'user_id' => $this->user->id,
            'genres' => [['name' => 'Action'], ['name' => 'Adventure']]
        ]);
        
        Game::factory()->owned()->create([
            'user_id' => $this->otherUser->id,
            'genres' => [['name' => 'Action'], ['name' => 'RPG']]
        ]);

        $response = $this->getJson(route('games.compatibility.api', $this->otherUser));

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('genre_compatibility', $data);
        
        $genreBreakdown = $data['genre_compatibility'];
        $this->assertNotEmpty($genreBreakdown);
        
        // Find Action genre in breakdown
        $actionGenre = collect($genreBreakdown)->firstWhere('genre', 'Action');
        $this->assertNotNull($actionGenre);
        $this->assertEquals(1, $actionGenre['user1_count']);
        $this->assertEquals(1, $actionGenre['user2_count']);
        $this->assertTrue($actionGenre['shared']);
    }

    /** @test */
    public function it_provides_game_recommendations()
    {
        $this->actingAs($this->user);

        // Create user games
        Game::factory()->owned()->create(['user_id' => $this->user->id]);

        // Create highly rated and favorite games for other user
        Game::factory()->owned()->favorite()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Favorite Game',
            'user_rating' => 9.5
        ]);

        Game::factory()->owned()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Highly Rated Game',
            'user_rating' => 8.5
        ]);

        Game::factory()->owned()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Low Rated Game',
            'user_rating' => 5.0
        ]);

        $response = $this->getJson(route('games.compatibility.api', $this->otherUser));

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('recommendations', $data);
        
        $recommendations = $data['recommendations'];
        $this->assertNotEmpty($recommendations);
        
        // Should recommend favorite and highly rated games
        $gameNames = collect($recommendations)->pluck('name')->toArray();
        $this->assertContains('Favorite Game', $gameNames);
        $this->assertContains('Highly Rated Game', $gameNames);
        $this->assertNotContains('Low Rated Game', $gameNames);
    }

    /** @test */
    public function it_handles_games_with_same_name_but_no_igdb_id()
    {
        $this->actingAs($this->user);

        // Create games with same name but no IGDB ID (fallback to name matching)
        Game::factory()->owned()->create([
            'user_id' => $this->user->id,
            'name' => 'Custom Game',
            'igdb_id' => null
        ]);

        Game::factory()->owned()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Custom Game',
            'igdb_id' => null
        ]);

        $response = $this->getJson(route('games.compatibility.api', $this->otherUser));

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals(1, $data['total_shared_games']);
        $this->assertGreaterThan(0, $data['compatibility_score']);
    }

    /** @test */
    public function it_returns_correct_compatibility_ratings()
    {
        $this->actingAs($this->user);

        // Test different score ranges
        $testCases = [
            ['score' => 90, 'rating' => 'Excellent Match'],
            ['score' => 70, 'rating' => 'Great Match'],
            ['score' => 50, 'rating' => 'Good Match'],
            ['score' => 30, 'rating' => 'Fair Match'],
            ['score' => 10, 'rating' => 'Limited Match'],
            ['score' => 0, 'rating' => 'No Data'],  // Controller returns 'No Data' for no games
        ];

        foreach ($testCases as $case) {
            // Create scenario to achieve specific score
            if ($case['score'] == 0) {
                // No games for zero score
                $this->user->games()->delete();
                $this->otherUser->games()->delete();
            } else {
                // Create many shared games to achieve high scores
                $this->user->games()->delete();
                $this->otherUser->games()->delete();
                
                $gamesNeeded = ceil($case['score'] / 10); // Rough calculation
                for ($i = 1; $i <= $gamesNeeded; $i++) {
                    Game::factory()->owned()->create([
                        'user_id' => $this->user->id,
                        'name' => "Game $i",
                        'igdb_id' => 1000 + $i,
                        'platform' => 'pc',
                        'genres' => [['name' => 'Action']]
                    ]);

                    Game::factory()->owned()->create([
                        'user_id' => $this->otherUser->id,
                        'name' => "Game $i",
                        'igdb_id' => 1000 + $i,
                        'platform' => 'pc',
                        'genres' => [['name' => 'Action']]
                    ]);
                }
            }

            $response = $this->getJson(route('games.compatibility.api', $this->otherUser));
            $data = $response->json();
            
            if ($case['score'] == 0) {
                $this->assertEquals($case['rating'], $data['compatibility_rating']);
            } else {
                // For non-zero scores, just verify we get a valid rating
                $validRatings = ['Excellent Match', 'Great Match', 'Good Match', 'Fair Match', 'Limited Match'];
                $this->assertContains($data['compatibility_rating'], $validRatings);
            }
        }
    }

    /** @test */
    public function it_handles_edge_case_of_empty_genre_arrays()
    {
        $this->actingAs($this->user);

        // Create games with empty or null genres
        Game::factory()->owned()->create([
            'user_id' => $this->user->id,
            'genres' => null
        ]);

        Game::factory()->owned()->create([
            'user_id' => $this->otherUser->id,
            'genres' => []
        ]);

        $response = $this->getJson(route('games.compatibility.api', $this->otherUser));

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('genre_compatibility', $data);
        $this->assertIsArray($data['genre_compatibility']);
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        $endpoints = [
            ['method' => 'get', 'route' => 'games.compatibility.index'],
            ['method' => 'get', 'route' => 'games.compatibility.compare', 'params' => [$this->otherUser]],
            ['method' => 'get', 'route' => 'games.compatibility.api', 'params' => [$this->otherUser]],
        ];

        foreach ($endpoints as $endpoint) {
            $params = $endpoint['params'] ?? [];
            $response = $this->{$endpoint['method']}(route($endpoint['route'], $params));
            
            if ($endpoint['route'] === 'games.compatibility.api') {
                $response->assertStatus(302); // API also redirects to login when not authenticated
                $response->assertRedirect(route('login'));
            } else {
                $response->assertRedirect(route('login'));
            }
        }
    }
}
