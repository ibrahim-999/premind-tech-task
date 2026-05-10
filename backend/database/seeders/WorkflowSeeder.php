<?php

namespace Database\Seeders;

use App\Domains\Workflow\Models\Workflow;
use App\Domains\Workflow\Models\WorkflowStep;
use App\Domains\Workflow\Models\WorkflowVersion;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflow = Workflow::firstOrCreate(
            ['subject_type' => 'purchase_order'],
            [
                'name' => 'Standard Purchase Order Approval',
                'is_active' => true,
            ]
        );

        if ($workflow->versions()->where('is_published', true)->exists()) {
            return;
        }

        $version = $workflow->versions()->create([
            'version_number' => 1,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $managerStep = WorkflowStep::create([
            'workflow_version_id' => $version->id,
            'order' => 1,
            'name' => 'Manager Approval',
            'approval_mode' => 'single',
            'required_approvals' => 1,
        ]);
        $managerStep->approvers()->create([
            'resolver_type' => 'direct_manager',
            'config' => [],
        ]);

        $procurementStep = WorkflowStep::create([
            'workflow_version_id' => $version->id,
            'order' => 2,
            'name' => 'Procurement Review',
            'approval_mode' => 'parallel_any',
            'required_approvals' => 1,
        ]);
        $procurementStep->conditions()->create([
            'type' => 'field_eq',
            'config' => ['field' => 'category', 'value' => 'IT'],
        ]);
        $procurementStep->approvers()->create([
            'resolver_type' => 'role',
            'config' => ['role' => 'cto'],
        ]);
        $procurementStep->approvers()->create([
            'resolver_type' => 'role',
            'config' => ['role' => 'cfo'],
        ]);

        $financeStep = WorkflowStep::create([
            'workflow_version_id' => $version->id,
            'order' => 3,
            'name' => 'Finance Head Approval',
            'approval_mode' => 'single',
            'required_approvals' => 1,
        ]);
        $financeStep->approvers()->create([
            'resolver_type' => 'role',
            'config' => ['role' => 'finance_head'],
        ]);
        $financeStep->conditions()->create([
            'type' => 'amount_gte',
            'config' => ['amount' => 5000],
        ]);
    }
}
