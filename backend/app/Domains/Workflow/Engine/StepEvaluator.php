<?php

namespace App\Domains\Workflow\Engine;

use App\Domains\Workflow\Approvers\ApproverResolverRegistry;
use App\Domains\Workflow\Conditions\ConditionRegistry;
use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Models\ApprovalStepInstance;
use App\Domains\Workflow\Models\WorkflowStep;
use Illuminate\Support\Collection;

class StepEvaluator
{
    public function __construct(
        private readonly ConditionRegistry $conditions,
        private readonly ApproverResolverRegistry $resolvers,
    ) {}

    public function shouldApply(WorkflowStep $step, Approvable $subject): bool
    {
        $conditions = $step->conditions;

        if ($conditions->isEmpty()) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $this->conditions->evaluate($condition->type, $condition->config ?? [], $subject)) {
                return false;
            }
        }

        return true;
    }

    public function resolveAssigneesForStep(WorkflowStep $step, Approvable $subject): Collection
    {
        $resolved = collect();

        foreach ($step->approvers as $approver) {
            $users = $this->resolvers->resolve($approver->resolver_type, $approver->config ?? [], $subject);

            foreach ($users as $user) {
                $resolved->put($user->getKey(), [
                    'user' => $user,
                    'resolver_source' => $approver->resolver_type,
                ]);
            }
        }

        $submitterId = $subject->approvalSubmitter()->getKey();

        return $resolved
            ->reject(fn (array $row) => (int) $row['user']->getKey() === (int) $submitterId)
            ->reject(fn (array $row) => ! (bool) $row['user']->is_active)
            ->values();
    }

    public function resolveAssigneesForAdHoc(ApprovalStepInstance $stepInstance, Approvable $subject): Collection
    {
        $type = (string) $stepInstance->ad_hoc_resolver_type;
        $config = (array) ($stepInstance->ad_hoc_resolver_config ?? []);

        $users = $this->resolvers->resolve($type, $config, $subject);
        $submitterId = $subject->approvalSubmitter()->getKey();

        return $users
            ->reject(fn ($user) => (int) $user->getKey() === (int) $submitterId)
            ->reject(fn ($user) => ! (bool) $user->is_active)
            ->map(fn ($user) => ['user' => $user, 'resolver_source' => $type])
            ->values();
    }
}
