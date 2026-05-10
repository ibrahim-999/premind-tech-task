<?php

namespace Tests\Unit\Workflow\Conditions;

use App\Domains\Workflow\Conditions\Handlers\AlwaysTrue;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryApprovable;

class AlwaysTrueTest extends TestCase
{
    public function test_returns_true_for_any_subject(): void
    {
        $handler = new AlwaysTrue();
        $subject = new InMemoryApprovable();

        $this->assertTrue($handler->evaluate($subject, []));
    }

    public function test_ignores_config(): void
    {
        $handler = new AlwaysTrue();
        $subject = new InMemoryApprovable(amount: -1.0, attributes: ['anything' => 'goes']);

        $this->assertTrue($handler->evaluate($subject, ['unused' => 'value']));
    }

    public function test_config_schema_is_empty(): void
    {
        $this->assertSame([], (new AlwaysTrue())->configSchema());
    }
}
