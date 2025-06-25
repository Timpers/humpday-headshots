<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_dashboard()
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHas(['user', 'gamertags', 'platformStats']);
    }

    public function test_dashboard_shows_user_gamertags()
    {
        $gamertag = \App\Models\Gamertag::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'steam',
            'gamertag' => 'TestGamer123',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('TestGamer123');
        $response->assertViewHas('gamertags', function ($gamertags) use ($gamertag) {
            return $gamertags->contains('id', $gamertag->id);
        });
    }

    public function test_dashboard_shows_platform_statistics()
    {
        // Create gamertags on different platforms for the current user
        \App\Models\Gamertag::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'steam',
            'is_primary' => true,
        ]);
        \App\Models\Gamertag::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'xbox_live',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('platformStats', function ($stats) {
            return $stats->get('steam') === 1 && $stats->get('xbox_live') === 1;
        });
    }

    public function test_dashboard_loads_user_relationship()
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->id === $this->user->id;
        });
    }

    public function test_dashboard_with_no_gamertags()
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('gamertags', function ($gamertags) {
            return $gamertags->isEmpty();
        });
        $response->assertViewHas('platformStats', function ($stats) {
            return $stats->isEmpty();
        });
    }
}
