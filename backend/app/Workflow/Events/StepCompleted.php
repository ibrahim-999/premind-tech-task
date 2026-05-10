<?php

namespace App\Workflow\Events;

use App\Workflow\Enums\StepInstanceStatus;
use App\Workflow\Models\ApprovalStepInstance;
use Illuminate\Foundation\Events\Dispatchable;

class StepCompleted
{
    use Dispatchable;

    public function __construct(
        public ApprovalStepInstance $stepInstance,
        public StepInstanceStatus $finalStatus,
    ) {}
}
