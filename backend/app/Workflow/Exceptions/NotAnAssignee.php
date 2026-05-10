<?php

namespace App\Workflow\Exceptions;

use RuntimeException;

class NotAnAssignee extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You are not an assignee of this approval step.');
    }
}
