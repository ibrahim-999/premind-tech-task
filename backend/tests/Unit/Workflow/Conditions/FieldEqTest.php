<?php

namespace Tests\Unit\Workflow\Conditions;

use App\Domains\Workflow\Conditions\Handlers\FieldEq;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryApprovable;

class FieldEqTest extends TestCase
{
    public function test_returns_true_when_field_value_matches(): void
    {
        $handler = new FieldEq();
        $subject = new InMemoryApprovable(attributes: ['category' => 'IT']);

        $this->assertTrue($handler->evaluate($subject, ['field' => 'category', 'value' => 'IT']));
    }

    public function test_returns_false_when_field_value_differs(): void
    {
        $handler = new FieldEq();
        $subject = new InMemoryApprovable(attributes: ['category' => 'HR']);

        $this->assertFalse($handler->evaluate($subject, ['field' => 'category', 'value' => 'IT']));
    }

    public function test_returns_false_when_field_is_missing(): void
    {
        $handler = new FieldEq();
        $subject = new InMemoryApprovable(attributes: ['department_id' => 3]);

        $this->assertFalse($handler->evaluate($subject, ['field' => 'category', 'value' => 'IT']));
    }

    public function test_loose_equality_across_compatible_types(): void
    {
        $handler = new FieldEq();
        $subject = new InMemoryApprovable(attributes: ['department_id' => 3]);

        $this->assertTrue($handler->evaluate($subject, ['field' => 'department_id', 'value' => '3']));
    }
}
