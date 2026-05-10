<?php

namespace App\Workflow\Enums;

enum ActionType: string
{
    case Approve = 'approve';
    case Reject = 'reject';
}
