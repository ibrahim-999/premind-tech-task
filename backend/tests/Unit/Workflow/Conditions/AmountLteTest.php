<?php

namespace Tests\Unit\Workflow\Conditions;

use App\Domains\Workflow\Conditions\Handlers\AmountLte;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryApprovable;

class AmountLteTest extends TestCase
{
    public function test_returns_true_when_amount_equals_threshold(): void
    {
        $handler = new AmountLte();
        $subject = new InMemoryApprovable(amount: 5000.0);

        $this->assertTrue($handler->evaluate($subject, ['amount' => 5000]));
    }

    public function test_returns_true_when_amount_below_threshold(): void
    {
        $handler = new AmountLte();
        $subject = new InMemoryApprovable(amount: 100.0);

        $this->assertTrue($handler->evaluate($subject, ['amount' => 5000]));
    }

    public function test_returns_false_when_amount_above_threshold(): void
    {
        $handler = new AmountLte();
        $subject = new InMemoryApprovable(amount: 5000.01);

        $this->assertFalse($handler->evaluate($subject, ['amount' => 5000]));
    }

    public function test_returns_false_when_amount_is_null(): void
    {
        $handler = new AmountLte();
        $subject = new InMemoryApprovable(amount: null);

        $this->assertFalse($handler->evaluate($subject, ['amount' => 5000]));
    }
}
