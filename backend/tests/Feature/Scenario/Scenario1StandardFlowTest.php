<?php

namespace Tests\Feature\Scenario;

use App\Domains\PurchaseOrder\Enums\PurchaseOrderStatus;
use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use App\Domains\Workflow\Enums\ProcessStatus;
use Tests\Support\ScenarioTestCase;
use Tests\Support\WorkflowFactory;

class Scenario1StandardFlowTest extends ScenarioTestCase
{
    public function test_eight_thousand_dollar_po_routes_through_manager_then_finance_head(): void
    {
        $this->seedRoles();
        $admin = $this->user('Aisha Admin', 'admin@test.local', 'admin');
        $sara = $this->user('Sara Manager', 'sara@test.local', 'manager', $admin->id, 1);
        $karim = $this->user('Karim Finance', 'karim@test.local', 'finance_head', $admin->id, 2);
        $ali = $this->user('Ali Developer', 'ali@test.local', 'requester', $sara->id, 3);

        WorkflowFactory::for(PurchaseOrder::class)
            ->name('Standard PO Flow')
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->publish();

        $createResponse = $this->postJsonAs($ali, '/api/v1/purchase-orders', [
            'title' => 'Two MacBooks',
            'category' => 'IT',
            'department_id' => 3,
            'items' => [['name' => 'MacBook Pro 16', 'quantity' => 2, 'unit_price' => 4000]],
        ], idempotent: false)->assertCreated();

        $poId = (int) $createResponse->json('data.id');

        $this->postJsonAs($ali, "/api/v1/purchase-orders/{$poId}/submit")->assertOk();

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(PurchaseOrderStatus::Submitted, $po->status);
        $this->assertSame('8000.00', $po->amount);

        $process = $po->approvalProcesses()->where('status', ProcessStatus::Pending->value)->firstOrFail();
        $managerStep = $process->fresh()->currentStepInstance;
        $this->assertSame('Manager Approval', $managerStep->step->name);
        $this->assertSame([$sara->id], $managerStep->assignees->pluck('user_id')->all());

        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$managerStep->id}/approve", [
            'comment' => 'Approved by manager',
        ])->assertOk();

        $financeStep = $process->fresh()->currentStepInstance;
        $this->assertSame('Finance Head Approval', $financeStep->step->name);
        $this->assertSame([$karim->id], $financeStep->assignees->pluck('user_id')->all());

        $this->postJsonAs($karim, "/api/v1/approvals/step-instances/{$financeStep->id}/approve")->assertOk();

        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
        $this->assertSame(PurchaseOrderStatus::Approved, $po->fresh()->status);
        $this->assertNotNull($po->fresh()->approved_at);
    }

    public function test_audit_log_records_each_engine_event_in_order(): void
    {
        $this->seedRoles();
        $admin = $this->user('Admin', 'admin2@test.local', 'admin');
        $sara = $this->user('Sara', 'sara2@test.local', 'manager', $admin->id, 1);
        $karim = $this->user('Karim', 'karim2@test.local', 'finance_head', $admin->id, 2);
        $ali = $this->user('Ali', 'ali2@test.local', 'requester', $sara->id, 3);

        WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->publish();

        $poId = (int) $this->postJsonAs($ali, '/api/v1/purchase-orders', [
            'title' => 'Server hardware',
            'category' => 'IT',
            'items' => [['name' => 'Server', 'quantity' => 1, 'unit_price' => 8000]],
        ], idempotent: false)->json('data.id');

        $this->postJsonAs($ali, "/api/v1/purchase-orders/{$poId}/submit")->assertOk();

        $po = PurchaseOrder::findOrFail($poId);
        $process = $po->approvalProcesses()->firstOrFail();

        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();
        $this->postJsonAs($karim, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();

        $events = $process->fresh()->auditLog->pluck('event_type')->all();

        $this->assertSame('process_started', $events[0]);
        $this->assertSame('process_approved', end($events));
        $this->assertSame(2, array_count_values($events)['step_entered'] ?? 0);
        $this->assertSame(2, array_count_values($events)['action_recorded'] ?? 0);
        $this->assertSame(2, array_count_values($events)['step_completed'] ?? 0);
    }

    public function test_finance_step_is_skipped_when_amount_below_threshold(): void
    {
        $this->seedRoles();
        $admin = $this->user('Admin', 'admin3@test.local', 'admin');
        $sara = $this->user('Sara', 'sara3@test.local', 'manager', $admin->id, 1);
        $this->user('Karim', 'karim3@test.local', 'finance_head', $admin->id, 2);
        $ali = $this->user('Ali', 'ali3@test.local', 'requester', $sara->id, 3);

        WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->publish();

        $poId = (int) $this->postJsonAs($ali, '/api/v1/purchase-orders', [
            'title' => 'Small purchase',
            'category' => 'IT',
            'items' => [['name' => 'Cable', 'quantity' => 1, 'unit_price' => 100]],
        ], idempotent: false)->json('data.id');

        $this->postJsonAs($ali, "/api/v1/purchase-orders/{$poId}/submit")->assertOk();

        $po = PurchaseOrder::findOrFail($poId);
        $process = $po->approvalProcesses()->firstOrFail();
        $managerStep = $process->fresh()->currentStepInstance;

        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$managerStep->id}/approve")->assertOk();

        $this->assertSame(PurchaseOrderStatus::Approved, $po->fresh()->status);
        $events = $process->fresh()->auditLog->pluck('event_type')->all();
        $this->assertContains('step_skipped', $events);
    }
}
