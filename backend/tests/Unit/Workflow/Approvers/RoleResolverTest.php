<?php

namespace Tests\Unit\Workflow\Approvers;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InMemoryApprovable;
use Tests\TestCase;

class RoleResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_active_users_with_the_requested_role(): void
    {
        $financeHead = Role::factory()->create(['name' => 'finance_head']);
        $other = Role::factory()->create(['name' => 'cto']);

        $karim = User::factory()->create();
        $karim->roles()->attach($financeHead);

        $ravi = User::factory()->create();
        $ravi->roles()->attach($other);

        $subject = new InMemoryApprovable(submitter: User::factory()->create());

        $result = (new RoleResolver())->resolve($subject, ['role' => 'finance_head']);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($karim));
    }

    public function test_excludes_inactive_users(): void
    {
        $role = Role::factory()->create(['name' => 'finance_head']);

        $active = User::factory()->create();
        $active->roles()->attach($role);

        $inactive = User::factory()->inactive()->create();
        $inactive->roles()->attach($role);

        $subject = new InMemoryApprovable(submitter: User::factory()->create());

        $result = (new RoleResolver())->resolve($subject, ['role' => 'finance_head']);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($active));
    }

    public function test_returns_empty_when_no_users_have_the_role(): void
    {
        Role::factory()->create(['name' => 'finance_head']);
        $subject = new InMemoryApprovable(submitter: User::factory()->create());

        $result = (new RoleResolver())->resolve($subject, ['role' => 'finance_head']);

        $this->assertTrue($result->isEmpty());
    }

    public function test_config_schema_requires_role(): void
    {
        $schema = (new RoleResolver())->configSchema();

        $this->assertArrayHasKey('role', $schema);
        $this->assertSame('string', $schema['role']['type']);
        $this->assertTrue($schema['role']['required']);
    }
}
