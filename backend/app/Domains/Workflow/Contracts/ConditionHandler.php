<?php

namespace App\Domains\Workflow\Contracts;

interface ConditionHandler
{
    public function evaluate(Approvable $subject, array $config): bool;

    public function configSchema(): array;
}
