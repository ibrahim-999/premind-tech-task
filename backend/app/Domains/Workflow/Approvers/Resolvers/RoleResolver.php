<?php

namespace App\Domains\Workflow\Approvers\Resolvers;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ApproverResolver;
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
