<?php

namespace Tests\Feature\Workflow;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use Tests\Support\EngineTestCase;
use Tests\Support\TestApprovable;
use Tests\Support\WorkflowFactory;

class WorkflowVersioningTest extends EngineTestCase
{
    public function test_in_flight_process_stays_on_its_pinned_version_when_a_new_version_publishes(): void
    {
        $v1 = WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $oldProcess = $this->engine->start($this->makeApprovable(submitter: $submitter));

        Role::factory()->create(['name' => 'cfo']);
        $cfo = User::factory()->create();
        $cfo->roles()->attach(Role::where('name', 'cfo')->first());

        $v2 = WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->step('CFO')->approvedBy(RoleResolver::class, ['role' => 'cfo'])
            ->publish();

        $this->assertNotSame($v1->id, $v2->id);
        $this->assertSame($v1->id, $oldProcess->fresh()->workflow_version_id);
    }

    public function test_processes_started_after_publish_use_the_new_version(): void
    {
        Role::factory()->create(['name' => 'cfo']);
        $cfo = User::factory()->create();
        $cfo->roles()->attach(Role::where('name', 'cfo')->first());

        $v1 = WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $v2 = WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->step('CFO')->approvedBy(RoleResolver::class, ['role' => 'cfo'])
            ->publish();

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $newProcess = $this->engine->start($this->makeApprovable(submitter: $submitter));

        $this->assertSame($v2->id, $newProcess->workflow_version_id);
    }

    public function test_unpublished_drafts_are_not_used_to_pin_processes(): void
    {
        $published = WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $draft = WorkflowFactory::for(TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->step('CFO')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $draft->update(['is_published' => false]);

        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);
        $process = $this->engine->start($this->makeApprovable(submitter: $submitter));

        $this->assertSame($published->id, $process->workflow_version_id);
    }
}
