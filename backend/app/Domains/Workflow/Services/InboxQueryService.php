<?php

namespace App\Domains\Workflow\Services;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use App\Domains\Workflow\Models\ApprovalStepInstance;
use Illuminate\Contracts\Pagination\CursorPaginator;

class InboxQueryService
{
    public function paginatedFor(User $user, int $perPage = 20): CursorPaginator
    {
        $userId = $user->getKey();

        return ApprovalStepInstance::query()
            ->where('status', StepInstanceStatus::Pending->value)
            ->whereHas('process', fn ($q) => $q->where('status', ProcessStatus::Pending->value))
            ->whereHas('assignees', function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('delegated_to_user_id', $userId);
            })
            ->with(['process.subject.requester', 'step'])
            ->orderByDesc('started_at')
            ->cursorPaginate($perPage);
    }
}
