<?php

namespace Tests\Feature\Auth;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login:127.0.0.1');
    }

    public function test_login_with_valid_credentials_returns_token_and_user(): void
    {
        $role = Role::factory()->create(['name' => 'manager', 'label' => 'Manager']);
        $user = User::factory()->create([
            'email' => 'sara@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $user->roles()->attach($role);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'sara@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'roles'],
            ])
            ->assertJsonPath('user.email', 'sara@example.com')
            ->assertJsonPath('user.roles.0', 'manager')
            ->assertJsonPath('token_type', 'bearer');
    }

    public function test_login_with_wrong_password_returns_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'sara@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'sara@example.com',
            'password' => 'WRONG',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error', 'invalid_credentials');
    }

    public function test_login_with_missing_fields_returns_validation_error(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonPath('details.email.0', 'The email field is required.')
            ->assertJsonPath('details.password.0', 'The password field is required.');
    }

    public function test_login_with_inactive_user_returns_account_inactive(): void
    {
        User::factory()->inactive()->create([
            'email' => 'sara@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'sara@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'account_inactive');
    }

    public function test_login_is_throttled_after_five_attempts_per_minute(): void
    {
        RateLimiter::clear('login:127.0.0.1');

        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'nope@example.com',
                'password' => 'wrong',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nope@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('error', 'rate_limited');
    }
}
