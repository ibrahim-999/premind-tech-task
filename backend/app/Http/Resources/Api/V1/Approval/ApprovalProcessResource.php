<?php

namespace App\Http\Resources\Api\V1\Approval;

use App\Http\Resources\Api\V1\Workflow\WorkflowVersionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalProcessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'status' => $this->status->value,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'current_step_instance_id' => $this->current_step_instance_id,
            'workflow_version' => WorkflowVersionResource::make($this->whenLoaded('version')),
            'current_step_instance' => ApprovalStepInstanceResource::make($this->whenLoaded('currentStepInstance')),
            'step_instances' => ApprovalStepInstanceResource::collection($this->whenLoaded('stepInstances')),
            'audit_log' => AuditLogEntryResource::collection($this->whenLoaded('auditLog')),
        ];
    }
}
