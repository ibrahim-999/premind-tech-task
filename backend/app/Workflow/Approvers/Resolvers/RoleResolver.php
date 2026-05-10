<?php

namespace App\Workflow\Approvers\Resolvers;

use App\Models\User;
use App\Workflow\Contracts\Approvable;
use App\Workflow\Contracts\ApproverResolver;
use Illuminate\Support\Collection;

class RoleResolver implements ApproverResolver
{
    public function resolve(Approvable $subject, array $config): Collection
    {
        return User::query()
            ->active()
            ->withRole((string) $config['role'])
            ->get();
    }

    public function configSchema(): array
    {
        return [
            'role' => ['type' => 'string', 'required' => true],
        ];
    }
}
