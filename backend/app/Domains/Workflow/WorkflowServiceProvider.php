<?php

namespace App\Domains\Workflow;

use App\Domains\Workflow\Approvers\ApproverResolverRegistry;
use App\Domains\Workflow\Approvers\Resolvers\DepartmentHeadResolver;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use App\Domains\Workflow\Approvers\Resolvers\SpecificUserResolver;
use App\Domains\Workflow\Conditions\ConditionRegistry;
use App\Domains\Workflow\Conditions\Handlers\AlwaysTrue;
use App\Domains\Workflow\Conditions\Handlers\AmountBetween;
use App\Domains\Workflow\Conditions\Handlers\AmountGte;
use App\Domains\Workflow\Conditions\Handlers\AmountLte;
use App\Domains\Workflow\Conditions\Handlers\FieldEq;
use App\Domains\Workflow\Conditions\Handlers\FieldIn;
use App\Domains\Workflow\Engine\StepEvaluator;
use App\Domains\Workflow\Engine\WorkflowEngine;
use App\Domains\Workflow\Listeners\WriteAuditLog;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConditionRegistry::class, function () {
            $registry = new ConditionRegistry();
            $registry->register('amount_gte', new AmountGte());
            $registry->register('amount_lte', new AmountLte());
            $registry->register('amount_between', new AmountBetween());
            $registry->register('field_eq', new FieldEq());
            $registry->register('field_in', new FieldIn());
            $registry->register('always_true', new AlwaysTrue());
            return $registry;
        });

        $this->app->singleton(ApproverResolverRegistry::class, function ($app) {
            $registry = new ApproverResolverRegistry();
            $registry->register('role', $app->make(RoleResolver::class));
            $registry->register('direct_manager', $app->make(DirectManagerResolver::class));
            $registry->register('specific_user', $app->make(SpecificUserResolver::class));
            $registry->register('department_head', $app->make(DepartmentHeadResolver::class));
            return $registry;
        });

        $this->app->singleton(StepEvaluator::class);
        $this->app->singleton(WorkflowEngine::class);
    }

    public function boot(Dispatcher $events): void
    {
        $events->subscribe(WriteAuditLog::class);
    }
}
