<?php

namespace App\Domains\PurchaseOrder\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return $this === self::Approved || $this === self::Cancelled;
    }

    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }

    public function isCancellable(): bool
    {
        return $this === self::Draft || $this === self::Submitted || $this === self::Rejected;
    }
}
