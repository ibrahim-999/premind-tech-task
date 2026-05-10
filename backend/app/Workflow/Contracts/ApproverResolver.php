<?php

namespace App\Workflow\Contracts;

use Illuminate\Support\Collection;

interface ApproverResolver
{
    public function resolve(Approvable $subject, array $config): Collection;

    public function configSchema(): array;
}
