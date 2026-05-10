<?php

namespace Tests\Feature\Workflow;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use App\Domains\Workflow\Approvers\Resolvers\SpecificUserResolver;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Events\NoApproversAvailable;
use Illuminate\Support\Facades\Event;
use Tests\Support\EngineTestCase;
use Tests\Support\TestApprovable;
use Tests\Support\WorkflowFactory;

class SubmitterCannotSelfApproveTest extends EngineTestCase
{
    public function test_submitter_is_filtered_from_resolved_assignees(): void
    {
        $submitter = User::factory()->create();

        WorkflowFactory::for(TestApprovable::class)
            ->step('Self review')->approvedBy(SpecificUserResolver::class, ['user_id' => $submitter->id])
            ->publish();

        Event::fake([NoApproversAvailable::class]);

        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));
        $stepInstance = $process->fresh()->currentStepInstance;

        $this->assertCount(0, $stepInstance->assignees);
        $this->assertSame(ProcessStatus::Pending, $process->fresh()->status);
        Event::assertDispatched(NoApproversAvailable::class);
    }

    public function test_submitter_is_filtered_but_other_resolved_users_remain(): void
    {
        $role = Role::factory()->create(['name' => 'reviewer']);
        $other = User::factory()->create();
        $other->roles()->attach($role);
        $submitter = User::factory()->create();
        $submitter->roles()->attach($role);

        WorkflowFactory::for(TestApprovable::class)
            ->step('Reviewers')->approvedBy(RoleResolver::class, ['role' => 'reviewer'])
            ->publish();

        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));
        $stepInstance = $process->fresh()->currentStepInstance;

        $assigneeIds = $stepInstance->assignees->pluck('user_id')->all();
        $this->assertSame([$other->id], $assigneeIds);
    }
}
