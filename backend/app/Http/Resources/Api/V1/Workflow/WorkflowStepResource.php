<?php

namespace App\Http\Resources\Api\V1\Workflow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => (int) $this->order,
            'name' => $this->name,
            'approval_mode' => $this->approval_mode->value,
            'required_approvals' => (int) $this->required_approvals,
            'conditions' => WorkflowStepConditionResource::collection($this->whenLoaded('conditions')),
            'approvers' => WorkflowStepApproverResource::collection($this->whenLoaded('approvers')),
        ];
    }
}
