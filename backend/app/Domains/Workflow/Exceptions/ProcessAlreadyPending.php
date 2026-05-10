<?php

namespace App\Domains\Workflow\Exceptions;

use RuntimeException;

class ProcessAlreadyPending extends RuntimeException
{
    public function __construct(string $subjectType, int|string $subjectId)
    {
        parent::__construct("A pending approval process already exists for {$subjectType}#{$subjectId}.");
    }
}
