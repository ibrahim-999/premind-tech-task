<?php

namespace App\Domains\Workflow\Conditions\Handlers;

use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ConditionHandler;

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
