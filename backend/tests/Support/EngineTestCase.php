<?php

namespace Tests\Support;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Engine\WorkflowEngine;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class EngineTestCase extends TestCase
{
    use RefreshDatabase;

    protected WorkflowEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::enforceMorphMap(['test_approvable' => TestApprovable::class]);

        $this->engine = app(WorkflowEngine::class);
    }

    protected function makeApprovable(?float $amount = null, array $overrides = [], ?User $submitter = null): TestApprovable
    {
        $submitter ??= User::factory()->create();

        $row = TestApprovable::create(array_merge([
            'name' => 'Test subject',
            'amount' => $amount,
            'category' => 'IT',
            'department_id' => 1,
            'submitter_id' => $submitter->id,
        ], $overrides));

        return $row->fresh();
    }
}
