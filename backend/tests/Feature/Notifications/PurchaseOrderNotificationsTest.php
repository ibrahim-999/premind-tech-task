<?php

namespace Tests\Feature\Notifications;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\PurchaseOrder\Notifications\PurchaseOrderApprovedNotification;
use App\Domains\PurchaseOrder\Notifications\PurchaseOrderRejectedNotification;
use App\Domains\PurchaseOrder\Notifications\StepAssignedNotification;
use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Notification;
use Tests\Support\ScenarioTestCase;
use Tests\Support\WorkflowFactory;

class PurchaseOrderNotificationsTest extends ScenarioTestCase
{
    public function test_assignee_receives_step_assigned_notification_when_step_is_entered(): void
    {
        Notification::fake();

        [$ali, $sara] = $this->seedTeamAndWorkflow();
        $this->createAndSubmit($ali, [['name' => 'Cable', 'quantity' => 1, 'unit_price' => 100]]);

        Notification::assertSentTo($sara, StepAssignedNotification::class);
    }

    public function test_requester_receives_approval_notification_on_final_approve(): void
    {
        Notification::fake();

        [$ali, $sara] = $this->seedTeamAndWorkflow();
        $poId = $this->createAndSubmit($ali, [['name' => 'Cable', 'quantity' => 1, 'unit_price' => 100]]);

        $process = PurchaseOrder::findOrFail($poId)->approvalProcesses()->firstOrFail();
        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();

        Notification::assertSentTo($ali, PurchaseOrderApprovedNotification::class);
    }

    public function test_requester_receives_rejection_notification_with_reason(): void
    {
        Notification::fake();

        [$ali, $sara] = $this->seedTeamAndWorkflow();
        $poId = $this->createAndSubmit($ali, [['name' => 'Cable', 'quantity' => 1, 'unit_price' => 100]]);

        $process = PurchaseOrder::findOrFail($poId)->approvalProcesses()->firstOrFail();
        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/reject", [
            'reason' => 'Quote missing breakdown',
        ])->assertOk();

        Notification::assertSentTo(
            $ali,
            PurchaseOrderRejectedNotification::class,
            fn (PurchaseOrderRejectedNotification $notification) => $notification->reason === 'Quote missing breakdown',
        );
    }

    public function test_only_active_step_assignees_receive_step_assigned(): void
    {
        Notification::fake();

        $this->seedRoles();
        $admin = $this->user('Admin', 'admin@n.local', 'admin');
        $sara = $this->user('Sara', 'sara@n.local', 'manager', $admin->id, 1);
        $karim = $this->user('Karim', 'karim@n.local', 'finance_head', $admin->id, 2);
        $ali = $this->user('Ali', 'ali@n.local', 'requester', $sara->id, 3);

        WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->publish();

        $poId = $this->createAndSubmit($ali, [['name' => 'Server', 'quantity' => 1, 'unit_price' => 8000]]);

        Notification::assertSentTo($sara, StepAssignedNotification::class);
        Notification::assertNotSentTo($karim, StepAssignedNotification::class);

        $process = PurchaseOrder::findOrFail($poId)->approvalProcesses()->firstOrFail();
        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();

        Notification::assertSentTo($karim, StepAssignedNotification::class);
    }

    public function test_no_notifications_dispatched_for_non_purchase_order_subjects(): void
    {
        Notification::fake();

        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'test_approvable' => \Tests\Support\TestApprovable::class,
        ]);

        $this->seedRoles();
        $manager = User::factory()->create();
        $submitter = User::factory()->create(['manager_id' => $manager->id]);

        WorkflowFactory::for(\Tests\Support\TestApprovable::class)
            ->step('Manager')->approvedBy(DirectManagerResolver::class)
            ->publish();

        $subject = \Tests\Support\TestApprovable::create([
            'name' => 'Generic',
            'amount' => 100,
            'submitter_id' => $submitter->id,
        ]);

        app(\App\Domains\Workflow\Engine\WorkflowEngine::class)->start($subject);

        Notification::assertNothingSent();
    }

    public function test_each_notification_class_implements_should_queue_after_commit(): void
    {
        $po = PurchaseOrder::create([
            'requester_id' => User::factory()->create()->id,
            'title' => 'Anything',
            'category' => 'IT',
        ]);

        $approved = new PurchaseOrderApprovedNotification($po);
        $rejected = new PurchaseOrderRejectedNotification($po, 'reason');

        $this->assertInstanceOf(ShouldQueue::class, $approved);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $approved);
        $this->assertInstanceOf(ShouldQueue::class, $rejected);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $rejected);
    }

    /** @return array{0: User, 1: User} */
    private function seedTeamAndWorkflow(): array
    {
        $this->seedRoles();
        $admin = $this->user('Admin', 'admin@n.local', 'admin');
        $sara = $this->user('Sara', 'sara@n.local', 'manager', $admin->id, 1);
        $ali = $this->user('Ali', 'ali@n.local', 'requester', $sara->id, 3);

        WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->publish();

        return [$ali, $sara];
    }

    private function createAndSubmit(User $user, array $items): int
    {
        $poId = (int) $this->postJsonAs($user, '/api/v1/purchase-orders', [
            'title' => 'Test PO',
            'category' => 'IT',
            'items' => $items,
        ], idempotent: false)->json('data.id');

        $this->postJsonAs($user, "/api/v1/purchase-orders/{$poId}/submit")->assertOk();

        return $poId;
    }
}
