<?php

namespace App\Domains\Workflow\Enums;

enum ProcessStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return $this !== self::Pending;
    }
}
