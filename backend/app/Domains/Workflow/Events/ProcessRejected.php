<?php

namespace App\Domains\Workflow\Events;

use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Models\ApprovalProcess;
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
