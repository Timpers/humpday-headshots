<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisteredUserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_create_displays_registration_form()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    public function test_create_redirects_authenticated_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_store_creates_user_and_logs_them_in()
    {
        Event::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $userData);

        $response->assertRedirect(route('dashboard'));

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Verify password was hashed
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->getAuthPassword()));

        // Verify user is logged in
        $this->assertAuthenticatedAs($user);

        // Verify Registered event was fired
        Event::assertDispatched(Registered::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->post(route('register'), []);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password'
        ]);
    }

    public function test_store_validates_name_is_string()
    {
        $response = $this->post(route('register'), [
            'name' => 123, // Not a string
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_name_max_length()
    {
        $response = $this->post(route('register'), [
            'name' => str_repeat('a', 256), // Too long
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_email_is_required()
    {
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_email_is_string()
    {
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 123, // Not a string
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_email_is_lowercase()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'JOHN@EXAMPLE.COM', // Uppercase email
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $userData);

        // Laravel's 'lowercase' validation rule requires email to be lowercase
        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_email_format()
    {
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'not-an-email', // Invalid email format
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_email_max_length()
    {
        $longEmail = str_repeat('a', 250) . '@example.com'; // Too long

        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => $longEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_email_is_unique()
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'existing@example.com', // Duplicate email
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_password_is_required()
    {
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_store_validates_password_confirmation()
    {
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_store_validates_password_confirmation_is_required()
    {
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            // No password_confirmation
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_store_validates_password_meets_default_requirements()
    {
        // Test with a password that's too short
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123', // Too short
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_store_prevents_duplicate_registration_attempts()
    {
        Event::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // First registration should succeed
        $response1 = $this->post(route('register'), $userData);
        $response1->assertRedirect(route('dashboard'));

        // Logout the user
        Auth::logout();

        // Second registration with same email should fail
        $response2 = $this->post(route('register'), $userData);
        $response2->assertSessionHasErrors(['email']);

        // Verify only one user was created
        $this->assertEquals(1, User::where('email', 'john@example.com')->count());
    }

    public function test_store_redirects_authenticated_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Verify no new user was created
        $this->assertEquals(1, User::count());
    }

    public function test_store_handles_long_valid_name()
    {
        Event::fake();

        $userData = [
            'name' => str_repeat('a', 255), // Maximum allowed length
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $userData);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'name' => str_repeat('a', 255),
            'email' => 'john@example.com',
        ]);
    }

    public function test_store_handles_long_valid_email()
    {
        Event::fake();

        // Create an email that's exactly 255 characters (the maximum)
        $localPart = str_repeat('a', 240);
        $email = $localPart . '@example.com'; // Total: 252 characters

        $userData = [
            'name' => 'John Doe',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $userData);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => $email,
        ]);
    }

    public function test_store_trims_whitespace_from_inputs()
    {
        Event::fake();

        $userData = [
            'name' => '  John Doe  ', // With whitespace
            'email' => '  john@example.com  ', // With whitespace
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $userData);

        $response->assertRedirect(route('dashboard'));

        // Laravel should trim whitespace automatically for most validation rules
        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
    }

    public function test_registered_event_contains_correct_user_data()
    {
        Event::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $userData);

        $response->assertRedirect(route('dashboard'));

        Event::assertDispatched(Registered::class, function ($event) {
            return $event->user->name === 'John Doe' &&
                   $event->user->email === 'john@example.com' &&
                   $event->user->exists; // User should be persisted
        });
    }

    public function test_user_is_authenticated_after_registration()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->assertGuest(); // Initially not authenticated

        $response = $this->post(route('register'), $userData);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticated(); // Should be authenticated after registration

        // Verify it's the correct user
        $user = User::where('email', 'john@example.com')->first();
        $this->assertEquals(Auth::id(), $user->id);
    }
}
