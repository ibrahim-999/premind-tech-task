<?php

namespace App\Domains\Workflow\Enums;

enum ActionType: string
{
    case Approve = 'approve';
    case Reject = 'reject';
}
