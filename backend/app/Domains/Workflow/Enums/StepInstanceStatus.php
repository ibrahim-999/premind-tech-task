<?php

namespace App\Domains\Workflow\Enums;

enum StepInstanceStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Skipped = 'skipped';
}
