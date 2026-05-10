<?php

namespace App\Workflow\Events;

use App\Workflow\Models\ApprovalAction;
use Illuminate\Foundation\Events\Dispatchable;

class ActionRecorded
{
    use Dispatchable;

    public function __construct(public ApprovalAction $action) {}
}
