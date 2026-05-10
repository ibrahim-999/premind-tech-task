<?php

namespace App\Workflow\Events;

use App\Workflow\Contracts\Approvable;
use App\Workflow\Models\ApprovalProcess;
use Illuminate\Foundation\Events\Dispatchable;

class ProcessRejected
{
    use Dispatchable;

    public function __construct(
        public ApprovalProcess $process,
        public Approvable $subject,
        public string $reason,
    ) {}
}
