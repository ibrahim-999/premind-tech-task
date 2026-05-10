<?php

namespace App\Workflow\Events;

use App\Models\User;
use App\Workflow\Models\ApprovalStepInstance;
use Illuminate\Foundation\Events\Dispatchable;

class StepInjected
{
    use Dispatchable;

    public function __construct(
        public ApprovalStepInstance $stepInstance,
        public User $injectedBy,
        public string $reason,
    ) {}
}
