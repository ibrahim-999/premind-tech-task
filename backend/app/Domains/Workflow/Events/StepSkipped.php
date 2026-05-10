<?php

namespace App\Domains\Workflow\Events;

use App\Domains\Workflow\Models\ApprovalProcess;
use App\Domains\Workflow\Models\WorkflowStep;
use Illuminate\Foundation\Events\Dispatchable;

class StepSkipped
{
    use Dispatchable;

    public function __construct(
        public ApprovalProcess $process,
        public WorkflowStep $step,
    ) {}
}
