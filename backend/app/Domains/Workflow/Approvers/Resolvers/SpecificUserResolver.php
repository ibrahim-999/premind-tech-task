<?php

namespace App\Domains\Workflow\Approvers\Resolvers;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ApproverResolver;
use Illuminate\Support\Collection;

class SpecificUserResolver implements ApproverResolver
{
    public function resolve(Approvable $subject, array $config): Collection
    {
        return User::query()
            ->whereKey((int) $config['user_id'])
            ->active()
            ->get();
    }

    public function configSchema(): array
    {
        return [
            'user_id' => ['type' => 'integer', 'required' => true],
        ];
    }
}
