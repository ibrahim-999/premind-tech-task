<?php

namespace Tests\Feature\Workflow;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use App\Domains\Workflow\Events\ProcessCancelled;
use App\Domains\Workflow\Exceptions\InvalidActionState;
use Illuminate\Support\Facades\Event;
use Tests\Support\EngineTestCase;
use Tests\Support\TestApprovable;
use Tests\Support\WorkflowFactory;

class CancelAndRestartTest extends EngineTestCase
{
    public function test_cancel_marks_process_cancelled_and_pending_steps_skipped(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $admin = User::factory()->create();

        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));
        $stepInstance = $process->fresh()->currentStepInstance;

        $this->engine->cancel($process, $admin, 'admin discretion');

        $this->assertSame(ProcessStatus::Cancelled, $process->fresh()->status);
        $this->assertNotNull($process->fresh()->completed_at);
        $this->assertSame(StepInstanceStatus::Skipped, $stepInstance->fresh()->status);
    }

    public function test_cancel_dispatches_process_cancelled_event(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $admin = User::factory()->create();

        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));

        Event::fake([ProcessCancelled::class]);

        $this->engine->cancel($process, $admin, 'cleanup');

        Event::assertDispatched(
            ProcessCancelled::class,
            fn (ProcessCancelled $e) => $e->reason === 'cleanup' && $e->process->id === $process->id,
        );
    }

    public function test_cancel_throws_when_process_already_terminal(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $admin = User::factory()->create();

        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));
        $this->engine->cancel($process, $admin, 'first cancel');

        $this->expectException(InvalidActionState::class);
        $this->engine->cancel($process->fresh(), $admin, 'second cancel');
    }

    public function test_after_cancel_a_new_process_can_be_started_for_the_same_subject(): void
    {
        WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $admin = User::factory()->create();

        $subject = $this->makeApprovable(submitter: $submitter);
        $first = $this->engine->start($subject);
        $this->engine->cancel($first, $admin, 'restart please');

        $second = $this->engine->start($subject);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(ProcessStatus::Pending, $second->status);
    }
}
