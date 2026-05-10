<?php

namespace App\Http\Resources\Api\V1\Approval;

use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalStepAssigneeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resolver_source' => $this->resolver_source,
            'user' => UserResource::make($this->whenLoaded('user')),
            'delegated_to' => UserResource::make($this->whenLoaded('delegatedTo')),
        ];
    }
}
