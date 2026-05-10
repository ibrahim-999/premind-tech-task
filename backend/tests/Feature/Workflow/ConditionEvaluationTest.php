<?php

namespace Tests\Feature\Workflow;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use App\Domains\Workflow\Enums\ActionType;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use App\Domains\Workflow\Models\ApprovalStepInstance;
use Illuminate\Support\Str;
use Tests\Support\EngineTestCase;
use Tests\Support\TestApprovable;
use Tests\Support\WorkflowFactory;

class ConditionEvaluationTest extends EngineTestCase
{
    public function test_step_with_failing_condition_is_skipped(): void
    {
        $financeRole = Role::factory()->create(['name' => 'finance_head']);
        $finance = User::factory()->create();
        $finance->roles()->attach($financeRole);

        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->step('Finance')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $subject = $this->makeApprovable(amount: 1000, submitter: $submitter);

        $process = $this->engine->start($subject);

        $this->engine->submitAction(
            $process->fresh()->currentStepInstance,
            $manager,
            ActionType::Approve,
            null,
            (string) Str::uuid(),
        );

        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
        $instances = ApprovalStepInstance::where('approval_process_id', $process->id)->get();
        $byStatus = $instances->groupBy(fn ($i) => $i->status->value);
        $this->assertCount(1, $byStatus->get('approved', collect()));
        $this->assertCount(1, $byStatus->get('skipped', collect()));
    }

    public function test_step_with_passing_condition_is_entered(): void
    {
        $financeRole = Role::factory()->create(['name' => 'finance_head']);
        $finance = User::factory()->create();
        $finance->roles()->attach($financeRole);

        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->step('Finance')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $subject = $this->makeApprovable(amount: 8000, submitter: $submitter);

        $process = $this->engine->start($subject);

        $this->engine->submitAction(
            $process->fresh()->currentStepInstance,
            $manager,
            ActionType::Approve,
            null,
            (string) Str::uuid(),
        );

        $this->assertSame(ProcessStatus::Pending, $process->fresh()->status);
        $current = $process->fresh()->currentStepInstance;
        $this->assertSame('Finance', $current->step->name);
        $this->assertSame($finance->id, (int) $current->assignees->first()->user_id);
    }

    public function test_multiple_conditions_are_anded(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Specific window')->approvedBy(DirectManagerResolver::class)
                ->whenAmountGte(5000)
                ->whenFieldEq('category', 'IT')
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $subject = $this->makeApprovable(
            amount: 8000,
            overrides: ['category' => 'HR'],
            submitter: $submitter,
        );

        $process = $this->engine->start($subject);

        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
        $this->assertCount(
            0,
            ApprovalStepInstance::where('approval_process_id', $process->id)
                ->where('status', StepInstanceStatus::Pending->value)
                ->get(),
        );
    }

    public function test_field_in_routes_to_branch_when_value_matches(): void
    {
        $ctoRole = Role::factory()->create(['name' => 'cto']);
        $cto = User::factory()->create();
        $cto->roles()->attach($ctoRole);

        WorkflowFactory::for(TestApprovable::class)
            ->step('CTO branch')->approvedBy(RoleResolver::class, ['role' => 'cto'])
                ->whenFieldIn('category', ['IT', 'Engineering'])
            ->publish();

        $submitter = User::factory()->create();
        $subject = $this->makeApprovable(overrides: ['category' => 'IT'], submitter: $submitter);

        $process = $this->engine->start($subject);
        $current = $process->fresh()->currentStepInstance;

        $this->assertSame(ProcessStatus::Pending, $process->fresh()->status);
        $this->assertSame('CTO branch', $current->step->name);
        $this->assertSame($cto->id, (int) $current->assignees->first()->user_id);
    }
}
