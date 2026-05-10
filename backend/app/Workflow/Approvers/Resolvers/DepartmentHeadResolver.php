<?php

namespace App\Workflow\Approvers\Resolvers;

use App\Models\User;
use App\Workflow\Contracts\Approvable;
use App\Workflow\Contracts\ApproverResolver;
use Illuminate\Support\Collection;

class DepartmentHeadResolver implements ApproverResolver
{
    public function resolve(Approvable $subject, array $config): Collection
    {
        $departmentId = $subject->approvalSubmitter()->department_id;

        if ($departmentId === null) {
            return collect();
        }

        return User::query()
            ->where('department_id', $departmentId)
            ->where('is_department_head', true)
            ->active()
            ->get();
    }

    public function configSchema(): array
    {
        return [];
    }
}
