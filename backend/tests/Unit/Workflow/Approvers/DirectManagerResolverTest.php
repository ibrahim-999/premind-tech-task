<?php

namespace Tests\Unit\Workflow\Approvers;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InMemoryApprovable;
use Tests\TestCase;

class DirectManagerResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_submitters_active_manager(): void
    {
        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $subject = new InMemoryApprovable(submitter: $submitter);

        $result = (new DirectManagerResolver())->resolve($subject, []);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($manager));
    }

    public function test_returns_empty_when_submitter_has_no_manager(): void
    {
        $submitter = User::factory()->create(['manager_id' => null]);
        $subject = new InMemoryApprovable(submitter: $submitter);

        $result = (new DirectManagerResolver())->resolve($subject, []);

        $this->assertTrue($result->isEmpty());
    }

    public function test_returns_empty_when_manager_is_inactive(): void
    {
        $manager = User::factory()->inactive()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $subject = new InMemoryApprovable(submitter: $submitter);

        $result = (new DirectManagerResolver())->resolve($subject, []);

        $this->assertTrue($result->isEmpty());
    }

    public function test_config_schema_is_empty(): void
    {
        $this->assertSame([], (new DirectManagerResolver())->configSchema());
    }
}
