<?php

namespace Tests\Feature\Auth;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_current_user_with_roles(): void
    {
        $user = User::factory()->create(['name' => 'Sara']);
        $user->roles()->attach(Role::factory()->create(['name' => 'manager', 'label' => 'Manager']));

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.name', 'Sara')
            ->assertJsonPath('data.roles.0', 'manager');
    }

    public function test_me_without_token_returns_token_missing(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('error', 'token_missing');
    }

    public function test_me_with_malformed_token_returns_token_invalid(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer not.a.real.token')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('error', 'token_invalid');
    }
}
