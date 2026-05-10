<?php

namespace App\Policies;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Models\Workflow;

class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->hasRole('admin');
    }

}
