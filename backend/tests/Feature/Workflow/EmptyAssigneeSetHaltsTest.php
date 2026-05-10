<?php

namespace Tests\Feature\Workflow;

use App\Domains\User\Models\Role;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use App\Domains\Workflow\Events\NoApproversAvailable;
use Illuminate\Support\Facades\Event;
use Tests\Support\EngineTestCase;
use Tests\Support\TestApprovable;
use Tests\Support\WorkflowFactory;

class EmptyAssigneeSetHaltsTest extends EngineTestCase
{
    public function test_step_with_no_resolvable_users_halts_process_pending(): void
    {
        Role::factory()->create(['name' => 'finance_head']);

        WorkflowFactory::for(TestApprovable::class)
            ->step('Finance')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])
            ->publish();

        Event::fake([NoApproversAvailable::class]);

        $process = $this->engine->start($this->makeApprovable());

        $this->assertSame(ProcessStatus::Pending, $process->fresh()->status);
        $stepInstance = $process->fresh()->currentStepInstance;
        $this->assertSame(StepInstanceStatus::Pending, $stepInstance->status);
        $this->assertCount(0, $stepInstance->assignees);
        Event::assertDispatched(NoApproversAvailable::class);
    }
}
