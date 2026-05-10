<?php

namespace App\Http\Resources\Api\V1\Workflow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowStepApproverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resolver_type' => $this->resolver_type,
            'config' => $this->config,
        ];
    }
}
