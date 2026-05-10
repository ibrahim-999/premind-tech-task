<?php

namespace App\Domains\Workflow\Exceptions;

use RuntimeException;

class NoActiveWorkflow extends RuntimeException
{
    public function __construct(string $subjectType)
    {
        parent::__construct("No active published workflow found for subject type: {$subjectType}");
    }
}
