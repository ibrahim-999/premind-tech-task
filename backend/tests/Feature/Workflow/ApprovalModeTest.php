<?php

namespace Tests\Feature\Workflow;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use App\Domains\Workflow\Enums\ActionType;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use Illuminate\Support\Str;
use Tests\Support\EngineTestCase;
use Tests\Support\TestApprovable;
use Tests\Support\WorkflowFactory;

class ApprovalModeTest extends EngineTestCase
{
    public function test_single_mode_completes_after_one_approval(): void
    {
        $reviewers = $this->reviewers(2);

        WorkflowFactory::for(TestApprovable::class)
            ->step('Review')->approvedBy(RoleResolver::class, ['role' => 'reviewer'])
            ->publish();

        $process = $this->engine->start($this->makeApprovable());
        $stepInstance = $process->fresh()->currentStepInstance;

        $this->engine->submitAction($stepInstance, $reviewers[0], ActionType::Approve, null, (string) Str::uuid());

        $this->assertSame(StepInstanceStatus::Approved, $stepInstance->fresh()->status);
        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
    }

    public function test_parallel_any_completes_after_first_approval(): void
    {
        $reviewers = $this->reviewers(3);

        WorkflowFactory::for(TestApprovable::class)
            ->step('Any reviewer')->inParallel()->approvedBy(RoleResolver::class, ['role' => 'reviewer'])
            ->publish();

        $process = $this->engine->start($this->makeApprovable());
        $stepInstance = $process->fresh()->currentStepInstance;

        $this->engine->submitAction($stepInstance, $reviewers[1], ActionType::Approve, null, (string) Str::uuid());

        $this->assertSame(StepInstanceStatus::Approved, $stepInstance->fresh()->status);
        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
    }

    public function test_parallel_all_requires_every_assignee_to_approve(): void
    {
        $reviewers = $this->reviewers(3);

        WorkflowFactory::for(TestApprovable::class)
            ->step('All reviewers')->requireAll()->approvedBy(RoleResolver::class, ['role' => 'reviewer'])
            ->publish();

        $process = $this->engine->start($this->makeApprovable());
        $stepInstance = $process->fresh()->currentStepInstance;

        $this->engine->submitAction($stepInstance, $reviewers[0], ActionType::Approve, null, (string) Str::uuid());
        $this->assertSame(StepInstanceStatus::Pending, $stepInstance->fresh()->status);

        $this->engine->submitAction($stepInstance, $reviewers[1], ActionType::Approve, null, (string) Str::uuid());
        $this->assertSame(StepInstanceStatus::Pending, $stepInstance->fresh()->status);

        $this->engine->submitAction($stepInstance, $reviewers[2], ActionType::Approve, null, (string) Str::uuid());
        $this->assertSame(StepInstanceStatus::Approved, $stepInstance->fresh()->status);
        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
    }

    public function test_quorum_completes_when_required_threshold_reached(): void
    {
        $reviewers = $this->reviewers(4);

        WorkflowFactory::for(TestApprovable::class)
            ->step('Quorum 2 of 4')->quorum(2)->approvedBy(RoleResolver::class, ['role' => 'reviewer'])
            ->publish();

        $process = $this->engine->start($this->makeApprovable());
        $stepInstance = $process->fresh()->currentStepInstance;

        $this->engine->submitAction($stepInstance, $reviewers[0], ActionType::Approve, null, (string) Str::uuid());
        $this->assertSame(StepInstanceStatus::Pending, $stepInstance->fresh()->status);

        $this->engine->submitAction($stepInstance, $reviewers[1], ActionType::Approve, null, (string) Str::uuid());
        $this->assertSame(StepInstanceStatus::Approved, $stepInstance->fresh()->status);
        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
    }

    /** @return array<int, User> */
    private function reviewers(int $count): array
    {
        $role = Role::firstOrCreate(['name' => 'reviewer'], ['label' => 'Reviewer']);

        return collect(range(1, $count))->map(function () use ($role) {
            $user = User::factory()->create();
            $user->roles()->attach($role);
            return $user;
        })->all();
    }
}
