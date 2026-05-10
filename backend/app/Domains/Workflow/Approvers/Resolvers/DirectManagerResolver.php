<?php

namespace App\Domains\Workflow\Approvers\Resolvers;

use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ApproverResolver;
use Illuminate\Support\Collection;

class DirectManagerResolver implements ApproverResolver
{
    public function resolve(Approvable $subject, array $config): Collection
    {
        $manager = $subject->approvalSubmitter()->manager;

        if ($manager === null || ! $manager->is_active) {
            return collect();
        }

        return collect([$manager]);
    }

    public function configSchema(): array
    {
        return [];
    }
}
