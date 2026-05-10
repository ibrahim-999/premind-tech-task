<?php

namespace App\Http\Resources\Api\V1\Approval;

use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InboxItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $process = $this->process;
        $subject = $process?->subject;

        return [
            'step_instance_id' => $this->id,
            'step_name' => $this->displayName(),
            'process_id' => $process?->id,
            'subject_type' => $process?->subject_type,
            'subject' => $this->subjectSummary($subject),
            'submitted_by' => $subject !== null && $subject->relationLoaded('requester')
                ? UserResource::make($subject->requester)
                : null,
            'submitted_at' => $process?->started_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
        ];
    }

    private function subjectSummary(mixed $subject): ?array
    {
        if ($subject === null) {
            return null;
        }

        return array_filter([
            'id' => $subject->getKey(),
            'title' => $subject->title ?? null,
            'amount' => isset($subject->amount) ? (float) $subject->amount : null,
            'category' => $subject->category ?? null,
            'status' => isset($subject->status) && method_exists($subject->status, 'value')
                ? $subject->status->value
                : ($subject->status ?? null),
        ], fn ($v) => $v !== null);
    }
}
