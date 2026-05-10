<?php

namespace App\Workflow\Conditions\Handlers;

use App\Workflow\Contracts\Approvable;
use App\Workflow\Contracts\ConditionHandler;

class AlwaysTrue implements ConditionHandler
{
    public function evaluate(Approvable $subject, array $config): bool
    {
        return true;
    }

    public function configSchema(): array
    {
        return [];
    }
}
