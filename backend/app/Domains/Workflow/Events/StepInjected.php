<?php

namespace App\Domains\Workflow\Events;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Models\ApprovalStepInstance;
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
