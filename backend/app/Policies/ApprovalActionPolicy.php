<?php

namespace App\Policies;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use App\Domains\Workflow\Models\ApprovalProcess;
use App\Domains\Workflow\Models\ApprovalStepInstance;

class ApprovalActionPolicy
{
    public function viewProcess(User $user, ApprovalProcess $process): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $subject = $process->subject;

        if ($subject !== null && property_exists($subject, 'requester_id') && (int) $subject->requester_id === (int) $user->getKey()) {
            return true;
        }

        return $process->stepInstances()
            ->whereHas('assignees', fn ($q) => $q->where('user_id', $user->getKey()))
            ->exists();
    }

    public function act(User $user, ApprovalStepInstance $stepInstance): bool
    {
        if ($stepInstance->status !== StepInstanceStatus::Pending) {
            return false;
        }

        if ($stepInstance->process->status !== ProcessStatus::Pending) {
            return false;
        }

        return $stepInstance->assignees()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->getKey())
                  ->orWhere('delegated_to_user_id', $user->getKey());
            })
            ->exists();
    }
}
