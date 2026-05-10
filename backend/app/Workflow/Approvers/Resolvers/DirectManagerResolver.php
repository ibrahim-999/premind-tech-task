<?php

namespace App\Workflow\Approvers\Resolvers;

use App\Workflow\Contracts\Approvable;
use App\Workflow\Contracts\ApproverResolver;
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
