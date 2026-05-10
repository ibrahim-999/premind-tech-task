<?php

namespace Tests\Feature\Workflow;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Enums\ActionType;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use App\Domains\Workflow\Exceptions\InvalidActionState;
use Illuminate\Support\Str;
use Tests\Support\EngineTestCase;
use Tests\Support\TestApprovable;
use Tests\Support\WorkflowFactory;

class AdHocStepInjectionTest extends EngineTestCase
{
    public function test_inject_creates_step_with_null_workflow_step_id_and_ad_hoc_metadata(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $admin = User::factory()->create();
        $extraReviewer = User::factory()->create();

        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));

        $injected = $this->engine->injectAdHocStep(
            $process,
            $admin,
            'Legal review',
            'specific_user',
            ['user_id' => $extraReviewer->id],
            'requested by CFO',
        );

        $this->assertNull($injected->workflow_step_id);
        $this->assertSame('Legal review', $injected->ad_hoc_name);
        $this->assertSame('specific_user', $injected->ad_hoc_resolver_type);
        $this->assertSame(['user_id' => $extraReviewer->id], $injected->ad_hoc_resolver_config);
        $this->assertSame('requested by CFO', $injected->ad_hoc_reason);
        $this->assertSame($admin->id, (int) $injected->added_by_user_id);
        $this->assertTrue($injected->isAdHoc());
    }

    public function test_inject_materializes_assignees_from_resolver(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $admin = User::factory()->create();
        $reviewer = User::factory()->create();

        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));

        $injected = $this->engine->injectAdHocStep(
            $process,
            $admin,
            'Spot check',
            'specific_user',
            ['user_id' => $reviewer->id],
            'random audit',
        );

        $this->assertCount(1, $injected->assignees);
        $this->assertSame($reviewer->id, (int) $injected->assignees->first()->user_id);
    }

    public function test_injected_step_records_approval_action_and_finalizes_step(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $admin = User::factory()->create();
        $reviewer = User::factory()->create();

        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));

        $injected = $this->engine->injectAdHocStep(
            $process,
            $admin,
            'Quick check',
            'specific_user',
            ['user_id' => $reviewer->id],
            'context',
        );

        $this->engine->submitAction($injected, $reviewer, ActionType::Approve, 'looks fine', (string) Str::uuid());

        $this->assertSame(StepInstanceStatus::Approved, $injected->fresh()->status);
        $action = $injected->fresh()->actions->first();
        $this->assertSame($reviewer->id, (int) $action->user_id);
        $this->assertSame(ActionType::Approve, $action->action);
        $this->assertSame('looks fine', $action->comment);
    }

    public function test_inject_throws_when_process_is_not_pending(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $admin = User::factory()->create();

        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));
        $stepInstance = $process->fresh()->currentStepInstance;
        $this->engine->submitAction($stepInstance, $manager, ActionType::Approve, null, (string) Str::uuid());

        $this->expectException(InvalidActionState::class);
        $this->engine->injectAdHocStep($process, $admin, 'Late', 'specific_user', ['user_id' => $admin->id], 'too late');
    }
}
