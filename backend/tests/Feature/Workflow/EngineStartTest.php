<?php

namespace Tests\Feature\Workflow;

use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use App\Domains\Workflow\Exceptions\NoActiveWorkflow;
use App\Domains\Workflow\Exceptions\ProcessAlreadyPending;
use App\Domains\Workflow\Models\ApprovalStepInstance;
use App\Domains\User\Models\User;
use Tests\Support\EngineTestCase;
use Tests\Support\TestApprovable;
use Tests\Support\WorkflowFactory;

class EngineStartTest extends EngineTestCase
{
    public function test_start_creates_pending_process_pinned_to_active_version(): void
    {
        $version = WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $subject = $this->makeApprovable(amount: 100, submitter: $submitter);

        $process = $this->engine->start($subject);

        $this->assertSame(ProcessStatus::Pending, $process->status);
        $this->assertSame($version->id, $process->workflow_version_id);
        $this->assertSame('test_approvable', $process->subject_type);
        $this->assertSame($subject->id, (int) $process->subject_id);
    }

    public function test_start_creates_first_step_instance_with_resolved_assignees(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $subject = $this->makeApprovable(submitter: $submitter);

        $process = $this->engine->start($subject);
        $step = ApprovalStepInstance::where('approval_process_id', $process->id)->firstOrFail();

        $this->assertSame(StepInstanceStatus::Pending, $step->status);
        $this->assertSame($step->id, (int) $process->fresh()->current_step_instance_id);
        $this->assertCount(1, $step->assignees);
        $this->assertSame($manager->id, (int) $step->assignees->first()->user_id);
    }

    public function test_start_throws_when_no_active_workflow_for_subject_type(): void
    {
        $subject = $this->makeApprovable();

        $this->expectException(NoActiveWorkflow::class);
        $this->engine->start($subject);
    }

    public function test_start_throws_process_already_pending_when_called_twice(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $subject = $this->makeApprovable(submitter: $submitter);

        $this->engine->start($subject);

        $this->expectException(ProcessAlreadyPending::class);
        $this->engine->start($subject);
    }

    public function test_start_throws_when_only_unpublished_version_exists(): void
    {
        $factory = WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class);

        $version = $factory->publish();
        $version->update(['is_published' => false]);

        $subject = $this->makeApprovable();

        $this->expectException(NoActiveWorkflow::class);
        $this->engine->start($subject);
    }

    public function test_finalizes_process_as_approved_when_no_steps_apply(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('IT only')->approvedBy(DirectManagerResolver::class)->whenFieldEq('category', 'HR')
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $subject = $this->makeApprovable(overrides: ['category' => 'IT'], submitter: $submitter);

        $process = $this->engine->start($subject);

        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
        $this->assertCount(
            0,
            ApprovalStepInstance::where('approval_process_id', $process->id)
                ->where('status', StepInstanceStatus::Pending->value)
                ->get(),
        );
    }
}
