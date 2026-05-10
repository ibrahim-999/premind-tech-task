<?php

namespace App\Http\Resources\Api\V1\PurchaseOrder;

use App\Http\Resources\Api\V1\Approval\ApprovalProcessResource;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'department_id' => $this->department_id,
            'amount' => (float) $this->amount,
            'status' => $this->status->value,
            'submission_count' => (int) $this->submission_count,
            'last_rejection_reason' => $this->last_rejection_reason,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'requester' => UserResource::make($this->whenLoaded('requester')),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'current_process' => ApprovalProcessResource::make($this->whenLoaded('currentProcess')),
        ];
    }
}
