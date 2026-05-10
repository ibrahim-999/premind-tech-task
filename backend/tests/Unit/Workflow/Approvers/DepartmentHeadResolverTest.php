<?php

namespace Tests\Unit\Workflow\Approvers;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\DepartmentHeadResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InMemoryApprovable;
use Tests\TestCase;

class DepartmentHeadResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_active_heads_in_the_submitters_department(): void
    {
        $head = User::factory()->departmentHead()->create(['department_id' => 3]);
        User::factory()->create(['department_id' => 3]);
        User::factory()->departmentHead()->create(['department_id' => 7]);

        $submitter = User::factory()->create(['department_id' => 3]);
        $subject = new InMemoryApprovable(submitter: $submitter);

        $result = (new DepartmentHeadResolver())->resolve($subject, []);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($head));
    }

    public function test_excludes_inactive_heads(): void
    {
        User::factory()->departmentHead()->inactive()->create(['department_id' => 3]);
        $submitter = User::factory()->create(['department_id' => 3]);
        $subject = new InMemoryApprovable(submitter: $submitter);

        $result = (new DepartmentHeadResolver())->resolve($subject, []);

        $this->assertTrue($result->isEmpty());
    }

    public function test_returns_empty_when_submitter_has_no_department(): void
    {
        User::factory()->departmentHead()->create(['department_id' => 3]);
        $submitter = User::factory()->create(['department_id' => null]);
        $subject = new InMemoryApprovable(submitter: $submitter);

        $result = (new DepartmentHeadResolver())->resolve($subject, []);

        $this->assertTrue($result->isEmpty());
    }

    public function test_config_schema_is_empty(): void
    {
        $this->assertSame([], (new DepartmentHeadResolver())->configSchema());
    }
}
