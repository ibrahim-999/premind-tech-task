<?php

namespace Tests\Unit\Workflow\Approvers;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\SpecificUserResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InMemoryApprovable;
use Tests\TestCase;

class SpecificUserResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_user_with_the_configured_id(): void
    {
        $target = User::factory()->create();
        User::factory()->count(2)->create();
        $subject = new InMemoryApprovable(submitter: User::factory()->create());

        $result = (new SpecificUserResolver())->resolve($subject, ['user_id' => $target->id]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($target));
    }

    public function test_returns_empty_when_target_is_inactive(): void
    {
        $target = User::factory()->inactive()->create();
        $subject = new InMemoryApprovable(submitter: User::factory()->create());

        $result = (new SpecificUserResolver())->resolve($subject, ['user_id' => $target->id]);

        $this->assertTrue($result->isEmpty());
    }

    public function test_returns_empty_when_user_does_not_exist(): void
    {
        $subject = new InMemoryApprovable(submitter: User::factory()->create());

        $result = (new SpecificUserResolver())->resolve($subject, ['user_id' => 999999]);

        $this->assertTrue($result->isEmpty());
    }

    public function test_config_schema_requires_user_id(): void
    {
        $schema = (new SpecificUserResolver())->configSchema();

        $this->assertArrayHasKey('user_id', $schema);
        $this->assertSame('integer', $schema['user_id']['type']);
        $this->assertTrue($schema['user_id']['required']);
    }
}
