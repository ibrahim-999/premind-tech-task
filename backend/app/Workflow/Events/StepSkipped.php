<?php

namespace App\Workflow\Events;

use App\Workflow\Models\ApprovalProcess;
use App\Workflow\Models\WorkflowStep;
use Illuminate\Foundation\Events\Dispatchable;

class StepSkipped
{
    use Dispatchable;

    public function __construct(
        public ApprovalProcess $process,
        public WorkflowStep $step,
    ) {}
}
