<?php

namespace App\Workflow\Events;

use App\Workflow\Models\ApprovalStepInstance;
use Illuminate\Foundation\Events\Dispatchable;

class NoApproversAvailable
{
    use Dispatchable;

    public function __construct(public ApprovalStepInstance $stepInstance) {}
}
