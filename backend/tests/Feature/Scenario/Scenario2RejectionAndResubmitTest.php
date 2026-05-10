<?php

namespace Tests\Feature\Scenario;

use App\Domains\PurchaseOrder\Enums\PurchaseOrderStatus;
use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use App\Domains\Workflow\Enums\ProcessStatus;
use Tests\Support\ScenarioTestCase;
use Tests\Support\WorkflowFactory;

class Scenario2RejectionAndResubmitTest extends ScenarioTestCase
{
    public function test_finance_rejection_freezes_first_process_and_marks_po_rejected_with_reason(): void
    {
        [$ali, $sara, $karim] = $this->seedTeamAndWorkflow();

        $poId = $this->createAndSubmit($ali, items: [
            ['name' => 'Server', 'quantity' => 1, 'unit_price' => 8000],
        ]);

        $po = PurchaseOrder::findOrFail($poId);
        $process = $po->approvalProcesses()->firstOrFail();

        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();

        $this->postJsonAs($karim, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/reject", [
            'reason' => 'Quote missing breakdown',
        ])->assertOk();

        $this->assertSame(ProcessStatus::Rejected, $process->fresh()->status);
        $this->assertSame(PurchaseOrderStatus::Rejected, $po->fresh()->status);
        $this->assertSame('Quote missing breakdown', $po->fresh()->last_rejection_reason);
        $this->assertNotNull($po->fresh()->rejected_at);
    }

    public function test_resubmit_after_edit_spawns_new_process_and_freezes_old_one(): void
    {
        [$ali, $sara, $karim] = $this->seedTeamAndWorkflow();

        $poId = $this->createAndSubmit($ali, items: [
            ['name' => 'Server', 'quantity' => 1, 'unit_price' => 8000],
        ]);

        $po = PurchaseOrder::findOrFail($poId);
        $firstProcess = $po->approvalProcesses()->firstOrFail();
        $firstHash = $po->fresh()->subject_hash;

        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$firstProcess->fresh()->current_step_instance_id}/approve")->assertOk();
        $this->postJsonAs($karim, "/api/v1/approvals/step-instances/{$firstProcess->fresh()->current_step_instance_id}/reject", [
            'reason' => 'Need cheaper alternative',
        ])->assertOk();

        $this->patchJsonAs($ali, "/api/v1/purchase-orders/{$poId}", [
            'items' => [['name' => 'Refurbished server', 'quantity' => 1, 'unit_price' => 6500]],
        ])->assertOk();

        $this->postJsonAs($ali, "/api/v1/purchase-orders/{$poId}/resubmit")->assertOk();

        $po = $po->fresh();
        $this->assertSame(PurchaseOrderStatus::Submitted, $po->status);
        $this->assertSame(2, $po->submission_count);
        $this->assertNotSame($firstHash, $po->subject_hash);

        $this->assertSame(ProcessStatus::Rejected, $firstProcess->fresh()->status);

        $secondProcess = $po->approvalProcesses()
            ->where('status', ProcessStatus::Pending->value)
            ->firstOrFail();
        $this->assertNotSame($firstProcess->id, $secondProcess->id);
        $this->assertSame('Manager Approval', $secondProcess->fresh()->currentStepInstance->step->name);
    }

    public function test_resubmit_is_forbidden_when_po_was_never_rejected(): void
    {
        [$ali] = $this->seedTeamAndWorkflow();

        $poId = (int) $this->postJsonAs($ali, '/api/v1/purchase-orders', [
            'title' => 'Brand new draft',
            'category' => 'IT',
            'items' => [['name' => 'Cable', 'quantity' => 1, 'unit_price' => 100]],
        ], idempotent: false)->json('data.id');

        $this->postJsonAs($ali, "/api/v1/purchase-orders/{$poId}/resubmit")->assertForbidden();
    }

    /** @return array{0: \App\Domains\User\Models\User, 1: \App\Domains\User\Models\User, 2: \App\Domains\User\Models\User} */
    private function seedTeamAndWorkflow(): array
    {
        $this->seedRoles();
        $admin = $this->user('Admin', 'admin@s2.local', 'admin');
        $sara = $this->user('Sara', 'sara@s2.local', 'manager', $admin->id, 1);
        $karim = $this->user('Karim', 'karim@s2.local', 'finance_head', $admin->id, 2);
        $ali = $this->user('Ali', 'ali@s2.local', 'requester', $sara->id, 3);

        WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->publish();

        return [$ali, $sara, $karim];
    }

    private function createAndSubmit($user, array $items): int
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
