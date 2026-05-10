<?php

namespace App\Workflow\Exceptions;

use RuntimeException;

class UnknownResolverType extends RuntimeException
{
    public function __construct(string $type)
    {
        parent::__construct("Unknown resolver type: {$type}");
    }
}
