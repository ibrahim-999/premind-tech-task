<?php

namespace App\Domains\Workflow\Services;

use App\Domains\Workflow\Models\ApprovalProcess;

class ProcessLoader
{
    private const FULL_DETAIL_RELATIONS = [
        'version.steps',
        'subject',
        'currentStepInstance.assignees.user',
        'stepInstances.assignees.user',
        'stepInstances.actions.user',
        'auditLog.actor',
    ];

    public function full(ApprovalProcess $process): ApprovalProcess
    {
        return $process->fresh(self::FULL_DETAIL_RELATIONS);
    }
}
