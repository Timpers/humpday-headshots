<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_displays_login_view()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_authenticated_user_cannot_access_login_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_store_validates_required_email()
    {
        $response = $this->post(route('login'), [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_email_format()
    {
        $response = $this->post(route('login'), [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_required_password()
    {
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_store_authenticates_user_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_store_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_store_fails_with_nonexistent_user()
    {
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_store_handles_remember_me_functionality()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        
        // Check that a remember token is set
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    public function test_store_redirects_to_intended_url()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Set an intended URL in the session
        $this->session(['url.intended' => route('gaming-sessions.index')]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('gaming-sessions.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_store_regenerates_session()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $oldSessionId = session()->getId();

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        
        // Session should be regenerated (new session ID)
        $this->assertNotEquals($oldSessionId, session()->getId());
    }

    public function test_destroy_logs_out_authenticated_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_destroy_invalidates_session()
    {
        $user = User::factory()->create();
        
        // Set some session data
        session(['test_key' => 'test_value']);
        $this->assertEquals('test_value', session('test_key'));

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        
        // Session data should be cleared
        $this->assertNull(session('test_key'));
    }

    public function test_destroy_regenerates_csrf_token()
    {
        $user = User::factory()->create();
        
        $oldToken = csrf_token();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        
        // CSRF token should be regenerated
        $this->assertNotEquals($oldToken, csrf_token());
    }

    public function test_destroy_works_for_guest_users()
    {
        // Test that logout route requires authentication (guest users should be redirected to login)
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_store_error_message_localization()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        
        // Check that the error message is the expected localized message
        $errors = session('errors');
        $this->assertEquals('The provided credentials do not match our records.', $errors->first('email'));
    }

    public function test_remember_me_checkbox_not_required()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Test login without remember checkbox (should default to false)
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_case_sensitive_email_authentication()
    {
        $user = User::factory()->create([
            'email' => 'Test@Example.com',
            'password' => Hash::make('password123'),
        ]);

        // Test with different case
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // This should fail because email case doesn't match
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
}
