<?php

namespace Tests\Support;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class ScenarioTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
    }

    protected function seedRoles(): void
    {
        foreach (['admin', 'manager', 'finance_head', 'cfo', 'cto', 'requester'] as $name) {
            Role::firstOrCreate(['name' => $name], ['label' => ucfirst($name)]);
        }
    }

    protected function user(
        string $name,
        string $email,
        string $role,
        ?int $managerId = null,
        ?int $departmentId = null,
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'manager_id' => $managerId,
            'department_id' => $departmentId,
        ]);

        $user->roles()->attach(Role::where('name', $role)->firstOrFail());

        return $user;
    }

    protected function authHeaders(User $user, bool $idempotent = false): array
    {
        $token = auth('api')->login($user);

        $headers = ['Authorization' => "Bearer {$token}"];

        if ($idempotent) {
            $headers['Idempotency-Key'] = (string) Str::uuid();
        }

        return $headers;
    }

    protected function postJsonAs(User $user, string $url, array $body = [], bool $idempotent = true): TestResponse
    {
        return $this->postJson($url, $body, $this->authHeaders($user, $idempotent));
    }

    protected function patchJsonAs(User $user, string $url, array $body = []): TestResponse
    {
        return $this->patchJson($url, $body, $this->authHeaders($user));
    }

    protected function getJsonAs(User $user, string $url): TestResponse
    {
        return $this->getJson($url, $this->authHeaders($user));
    }
}
