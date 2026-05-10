<?php

namespace App\Policies;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Models\WorkflowVersion;

class WorkflowVersionPolicy
{
    public function view(User $user, WorkflowVersion $version): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, WorkflowVersion $version): bool
    {
        return $user->hasRole('admin') && ! $version->is_published;
    }

    public function publish(User $user, WorkflowVersion $version): bool
    {
        return $user->hasRole('admin') && ! $version->is_published;
    }
}
