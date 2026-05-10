<?php

namespace Tests\Unit\Workflow\Conditions;

use App\Domains\Workflow\Conditions\Handlers\AmountGte;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryApprovable;

class AmountGteTest extends TestCase
{
    public function test_returns_true_when_amount_equals_threshold(): void
    {
        $handler = new AmountGte();
        $subject = new InMemoryApprovable(amount: 5000.0);

        $this->assertTrue($handler->evaluate($subject, ['amount' => 5000]));
    }

    public function test_returns_true_when_amount_above_threshold(): void
    {
        $handler = new AmountGte();
        $subject = new InMemoryApprovable(amount: 8000.0);

        $this->assertTrue($handler->evaluate($subject, ['amount' => 5000]));
    }

    public function test_returns_false_when_amount_below_threshold(): void
    {
        $handler = new AmountGte();
        $subject = new InMemoryApprovable(amount: 4999.99);

        $this->assertFalse($handler->evaluate($subject, ['amount' => 5000]));
    }

    public function test_returns_false_when_amount_is_null(): void
    {
        $handler = new AmountGte();
        $subject = new InMemoryApprovable(amount: null);

        $this->assertFalse($handler->evaluate($subject, ['amount' => 5000]));
    }

    public function test_config_schema_declares_required_amount(): void
    {
        $schema = (new AmountGte())->configSchema();

        $this->assertArrayHasKey('amount', $schema);
        $this->assertSame('number', $schema['amount']['type']);
        $this->assertTrue($schema['amount']['required']);
    }
}
