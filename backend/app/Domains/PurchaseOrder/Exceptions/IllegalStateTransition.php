<?php

namespace App\Domains\PurchaseOrder\Exceptions;

use App\Domains\PurchaseOrder\Enums\PurchaseOrderStatus;
use RuntimeException;

class IllegalStateTransition extends RuntimeException
{
    public function __construct(PurchaseOrderStatus $from, PurchaseOrderStatus $to)
    {
        parent::__construct("Cannot transition purchase order from {$from->value} to {$to->value}.");
    }
}
