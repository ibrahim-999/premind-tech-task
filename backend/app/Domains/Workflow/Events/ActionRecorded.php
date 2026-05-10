<?php

namespace App\Domains\Workflow\Events;

use App\Domains\Workflow\Models\ApprovalAction;
use Illuminate\Foundation\Events\Dispatchable;

class ActionRecorded
{
    use Dispatchable;

    public function __construct(public ApprovalAction $action) {}
}
