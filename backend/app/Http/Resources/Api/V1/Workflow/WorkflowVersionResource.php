<?php

namespace App\Http\Resources\Api\V1\Workflow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'version_number' => (int) $this->version_number,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'steps' => WorkflowStepResource::collection($this->whenLoaded('steps')),
        ];
    }
}
