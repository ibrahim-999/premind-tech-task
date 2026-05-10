<?php

namespace App\Http\Resources\Api\V1\Approval;

use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'payload' => $this->payload,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'actor' => UserResource::make($this->whenLoaded('actor')),
        ];
    }
}
