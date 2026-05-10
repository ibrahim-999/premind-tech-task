<?php

namespace Tests\Unit\Workflow\Conditions;

use App\Domains\Workflow\Conditions\Handlers\FieldIn;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryApprovable;

class FieldInTest extends TestCase
{
    public function test_returns_true_when_field_in_values(): void
    {
        $handler = new FieldIn();
        $subject = new InMemoryApprovable(attributes: ['category' => 'IT']);

        $this->assertTrue($handler->evaluate($subject, [
            'field' => 'category',
            'values' => ['IT', 'Operations'],
        ]));
    }

    public function test_returns_false_when_field_not_in_values(): void
    {
        $handler = new FieldIn();
        $subject = new InMemoryApprovable(attributes: ['category' => 'HR']);

        $this->assertFalse($handler->evaluate($subject, [
            'field' => 'category',
            'values' => ['IT', 'Operations'],
        ]));
    }

    public function test_returns_false_when_field_is_missing(): void
    {
        $handler = new FieldIn();
        $subject = new InMemoryApprovable(attributes: []);

        $this->assertFalse($handler->evaluate($subject, [
            'field' => 'category',
            'values' => ['IT'],
        ]));
    }

    public function test_returns_false_when_values_is_empty(): void
    {
        $handler = new FieldIn();
        $subject = new InMemoryApprovable(attributes: ['category' => 'IT']);

        $this->assertFalse($handler->evaluate($subject, [
            'field' => 'category',
            'values' => [],
        ]));
    }
}
