<?php

namespace App\Domains\Workflow\Events;

use App\Domains\Workflow\Models\ApprovalStepInstance;
use Illuminate\Foundation\Events\Dispatchable;

class NoApproversAvailable
{
    use Dispatchable;

    public function __construct(public ApprovalStepInstance $stepInstance) {}
}
