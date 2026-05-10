<?php

namespace Tests\Unit\Workflow;

use App\Domains\Workflow\Conditions\ConditionRegistry;
use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ConditionHandler;
use App\Domains\Workflow\Exceptions\UnknownConditionType;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryApprovable;

class ConditionRegistryTest extends TestCase
{
    public function test_register_and_evaluate_dispatches_to_handler(): void
    {
        $registry = new ConditionRegistry();
        $handler = $this->recordingHandler(true);
        $registry->register('always_true', $handler);
        $subject = new InMemoryApprovable();

        $result = $registry->evaluate('always_true', ['x' => 1], $subject);

        $this->assertTrue($result);
        $this->assertSame(['x' => 1], $handler->lastConfig);
        $this->assertSame($subject, $handler->lastSubject);
    }

    public function test_has_returns_true_for_registered_type_and_false_otherwise(): void
    {
        $registry = new ConditionRegistry();
        $registry->register('foo', $this->recordingHandler(true));

        $this->assertTrue($registry->has('foo'));
        $this->assertFalse($registry->has('bar'));
    }

    public function test_unknown_type_throws(): void
    {
        $registry = new ConditionRegistry();

        $this->expectException(UnknownConditionType::class);

        $registry->evaluate('does_not_exist', [], new InMemoryApprovable());
    }

    public function test_register_overwrites_previous_handler_for_same_type(): void
    {
        $registry = new ConditionRegistry();
        $registry->register('toggle', $this->recordingHandler(false));
        $registry->register('toggle', $this->recordingHandler(true));

        $this->assertTrue($registry->evaluate('toggle', [], new InMemoryApprovable()));
    }

    public function test_all_returns_registered_handlers(): void
    {
        $registry = new ConditionRegistry();
        $a = $this->recordingHandler(true);
        $b = $this->recordingHandler(false);
        $registry->register('a', $a);
        $registry->register('b', $b);

        $this->assertSame(['a' => $a, 'b' => $b], $registry->all());
    }

    private function recordingHandler(bool $returnValue): ConditionHandler
    {
        return new class($returnValue) implements ConditionHandler {
            public ?array $lastConfig = null;
            public ?Approvable $lastSubject = null;

            public function __construct(private readonly bool $returnValue) {}

            public function evaluate(Approvable $subject, array $config): bool
            {
                $this->lastSubject = $subject;
                $this->lastConfig = $config;

                return $this->returnValue;
            }

            public function configSchema(): array
            {
                return [];
            }
        };
    }
}
