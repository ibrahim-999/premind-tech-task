<?php

namespace Tests\Feature\Auth;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_issues_new_token(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user']);

        $newToken = $response->json('access_token');
        $this->assertNotEquals($token, $newToken);
    }

    public function test_refresh_after_logout_fails_with_token_blacklisted(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401)
            ->assertJsonPath('error', 'token_blacklisted');
    }
}
