<?php

namespace App\Domains\Workflow\Exceptions;

use RuntimeException;

class UnknownConditionType extends RuntimeException
{
    public function __construct(string $type)
    {
        parent::__construct("Unknown condition type: {$type}");
    }
}
