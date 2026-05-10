<?php

namespace App\Providers;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\Workflow\Models\ApprovalProcess;
use App\Domains\Workflow\Models\ApprovalStepInstance;
use App\Domains\Workflow\Models\Workflow;
use App\Domains\Workflow\Models\WorkflowVersion;
use App\Policies\ApprovalActionPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\WorkflowPolicy;
use App\Policies\WorkflowVersionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->getKey())
                : Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(ApprovalProcess::class, ApprovalActionPolicy::class);
        Gate::policy(ApprovalStepInstance::class, ApprovalActionPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(WorkflowVersion::class, WorkflowVersionPolicy::class);
    }
}
