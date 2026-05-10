<?php

namespace Tests\Feature\Scenario;

use App\Domains\PurchaseOrder\Enums\PurchaseOrderStatus;
use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\User\Models\User;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use App\Domains\Workflow\Enums\ProcessStatus;
use Tests\Support\ScenarioTestCase;
use Tests\Support\WorkflowFactory;

class Scenario3ConfigurationChangeTest extends ScenarioTestCase
{
    public function test_in_flight_po_keeps_v1_flow_when_v2_publishes_mid_process(): void
    {
        [$ali, $sara, $karim, $chen] = $this->seedTeam();

        $v1 = WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->publish();

        $poId = $this->createAndSubmit($ali, [
            ['name' => 'Server', 'quantity' => 1, 'unit_price' => 30000],
        ]);
        $po = PurchaseOrder::findOrFail($poId);
        $process = $po->approvalProcesses()->firstOrFail();
        $this->assertSame($v1->id, $process->workflow_version_id);

        $v2 = WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->step('CFO Approval')->approvedBy(RoleResolver::class, ['role' => 'cfo'])->whenAmountGte(25000)
            ->publish();

        $this->assertNotSame($v1->id, $v2->id);
        $this->assertSame($v1->id, $process->fresh()->workflow_version_id);

        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();
        $this->postJsonAs($karim, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();

        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
        $this->assertSame(PurchaseOrderStatus::Approved, $po->fresh()->status);

        $stepNames = $process->fresh()->stepInstances->pluck('step.name')->all();
        $this->assertNotContains('CFO Approval', $stepNames);
    }

    public function test_new_po_submitted_after_v2_publish_uses_v2_and_routes_to_cfo(): void
    {
        [$ali, $sara, $karim, $chen] = $this->seedTeam();

        WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->publish();

        $v2 = WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->step('CFO Approval')->approvedBy(RoleResolver::class, ['role' => 'cfo'])->whenAmountGte(25000)
            ->publish();

        $poId = $this->createAndSubmit($ali, [
            ['name' => 'Server', 'quantity' => 1, 'unit_price' => 30000],
        ]);

        $po = PurchaseOrder::findOrFail($poId);
        $process = $po->approvalProcesses()->firstOrFail();
        $this->assertSame($v2->id, $process->workflow_version_id);

        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();
        $this->postJsonAs($karim, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();

        $cfoStep = $process->fresh()->currentStepInstance;
        $this->assertSame('CFO Approval', $cfoStep->step->name);
        $this->assertSame([$chen->id], $cfoStep->assignees->pluck('user_id')->all());

        $this->postJsonAs($chen, "/api/v1/approvals/step-instances/{$cfoStep->id}/approve")->assertOk();

        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
        $this->assertSame(PurchaseOrderStatus::Approved, $po->fresh()->status);
    }

    public function test_thirty_thousand_po_below_finance_threshold_is_routed_only_through_two_steps_under_v2(): void
    {
        [$ali, $sara, $karim, $chen] = $this->seedTeam();

        WorkflowFactory::for(PurchaseOrder::class)
            ->step('Manager Approval')->approvedBy(DirectManagerResolver::class)
            ->step('Finance Head Approval')->approvedBy(RoleResolver::class, ['role' => 'finance_head'])->whenAmountGte(5000)
            ->step('CFO Approval')->approvedBy(RoleResolver::class, ['role' => 'cfo'])->whenAmountGte(25000)
            ->publish();

        $poId = $this->createAndSubmit($ali, [
            ['name' => 'Modest purchase', 'quantity' => 1, 'unit_price' => 8000],
        ]);

        $po = PurchaseOrder::findOrFail($poId);
        $process = $po->approvalProcesses()->firstOrFail();

        $this->postJsonAs($sara, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();
        $this->postJsonAs($karim, "/api/v1/approvals/step-instances/{$process->fresh()->current_step_instance_id}/approve")->assertOk();

        $this->assertSame(ProcessStatus::Approved, $process->fresh()->status);
        $stepNames = $process->fresh()->stepInstances->pluck('step.name')->all();
        $this->assertNotContains('CFO Approval', $stepNames);
    }

    /** @return array{0: User, 1: User, 2: User, 3: User} */
    private function seedTeam(): array
    {
        $this->seedRoles();
        $admin = $this->user('Admin', 'admin@s3.local', 'admin');
        $sara = $this->user('Sara', 'sara@s3.local', 'manager', $admin->id, 1);
        $karim = $this->user('Karim', 'karim@s3.local', 'finance_head', $admin->id, 2);
        $chen = $this->user('Chen', 'chen@s3.local', 'cfo', $admin->id, 2);
        $ali = $this->user('Ali', 'ali@s3.local', 'requester', $sara->id, 3);

        return [$ali, $sara, $karim, $chen];
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
