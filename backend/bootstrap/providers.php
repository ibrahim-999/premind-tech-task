<?php

use App\Providers\AppServiceProvider;
use App\Domains\PurchaseOrder\PurchaseOrderServiceProvider;
use App\Domains\Workflow\WorkflowServiceProvider;

return [
    AppServiceProvider::class,
    WorkflowServiceProvider::class,
    PurchaseOrderServiceProvider::class,
];
