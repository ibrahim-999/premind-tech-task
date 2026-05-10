<?php

namespace Tests\Unit\Workflow\Conditions;

use App\Domains\Workflow\Conditions\Handlers\AmountBetween;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryApprovable;

class AmountBetweenTest extends TestCase
{
    public function test_returns_true_inside_range_inclusive(): void
    {
        $handler = new AmountBetween();
        $subject = new InMemoryApprovable(amount: 7500.0);

        $this->assertTrue($handler->evaluate($subject, ['min' => 5000, 'max' => 10000]));
    }

    public function test_returns_true_at_lower_bound(): void
    {
        $handler = new AmountBetween();
        $subject = new InMemoryApprovable(amount: 5000.0);

        $this->assertTrue($handler->evaluate($subject, ['min' => 5000, 'max' => 10000]));
    }

    public function test_returns_true_at_upper_bound(): void
    {
        $handler = new AmountBetween();
        $subject = new InMemoryApprovable(amount: 10000.0);

        $this->assertTrue($handler->evaluate($subject, ['min' => 5000, 'max' => 10000]));
    }

    public function test_returns_false_below_min(): void
    {
        $handler = new AmountBetween();
        $subject = new InMemoryApprovable(amount: 4999.99);

        $this->assertFalse($handler->evaluate($subject, ['min' => 5000, 'max' => 10000]));
    }

    public function test_returns_false_above_max(): void
    {
        $handler = new AmountBetween();
        $subject = new InMemoryApprovable(amount: 10000.01);

        $this->assertFalse($handler->evaluate($subject, ['min' => 5000, 'max' => 10000]));
    }

    public function test_returns_false_when_amount_is_null(): void
    {
        $handler = new AmountBetween();
        $subject = new InMemoryApprovable(amount: null);

        $this->assertFalse($handler->evaluate($subject, ['min' => 5000, 'max' => 10000]));
    }
}
