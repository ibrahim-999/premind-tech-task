<?php

namespace App\Domains\Workflow\Events;

use App\Domains\Workflow\Enums\StepInstanceStatus;
use App\Domains\Workflow\Models\ApprovalStepInstance;
use Illuminate\Foundation\Events\Dispatchable;

class StepCompleted
{
    use Dispatchable;

    public function __construct(
        public ApprovalStepInstance $stepInstance,
        public StepInstanceStatus $finalStatus,
    ) {}
}
