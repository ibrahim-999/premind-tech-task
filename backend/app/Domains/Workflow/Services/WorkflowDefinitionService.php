<?php

namespace App\Domains\Workflow\Services;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Models\Workflow;
use App\Domains\Workflow\Models\WorkflowStep;
use App\Domains\Workflow\Models\WorkflowVersion;
use Illuminate\Support\Facades\DB;

class WorkflowDefinitionService
{
    public function createVersion(Workflow $workflow, array $stepsData): WorkflowVersion
    {
        return DB::transaction(function () use ($workflow, $stepsData) {
            $version = $workflow->versions()->create([
                'version_number' => ((int) $workflow->versions()->max('version_number')) + 1,
                'is_published' => false,
            ]);

            foreach ($stepsData as $idx => $step) {
                $this->createStep($version, $idx + 1, $step);
            }

            return $version->load('steps.conditions', 'steps.approvers');
        });
    }

    public function publish(WorkflowVersion $version, ?User $publisher): WorkflowVersion
    {
        $version->update([
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $publisher?->getKey(),
        ]);

        return $version->fresh()->load('steps.conditions', 'steps.approvers');
    }

    private function createStep(WorkflowVersion $version, int $order, array $data): void
    {
        $step = WorkflowStep::create([
            'workflow_version_id' => $version->id,
            'order' => $order,
            'name' => $data['name'],
            'approval_mode' => $data['approval_mode'] ?? 'single',
            'required_approvals' => $data['required_approvals'] ?? 1,
        ]);

        foreach (($data['conditions'] ?? []) as $cond) {
            $step->conditions()->create([
                'type' => $cond['type'],
                'config' => $cond['config'] ?? [],
            ]);
        }

        foreach ($data['approvers'] as $appr) {
            $step->approvers()->create([
                'resolver_type' => $appr['resolver_type'],
                'config' => $appr['config'] ?? [],
            ]);
        }
    }
}
