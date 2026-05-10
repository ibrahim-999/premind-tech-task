<?php

namespace App\Http\Resources\Api\V1\Approval;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalStepInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->displayName(),
            'status' => $this->status->value,
            'is_ad_hoc' => $this->isAdHoc(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'ad_hoc_reason' => $this->ad_hoc_reason,
            'assignees' => ApprovalStepAssigneeResource::collection($this->whenLoaded('assignees')),
            'actions' => ApprovalActionResource::collection($this->whenLoaded('actions')),
        ];
    }
}
