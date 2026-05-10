<?php

use App\Http\Controllers\Api\V1\Admin\AdminProcessController;
use App\Http\Controllers\Api\V1\Approval\ApprovalsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PurchaseOrder\PurchaseOrderController;
use App\Http\Controllers\Api\V1\Workflow\WorkflowsController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('jwt.auth')->group(function () {
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('jwt.auth')->group(function () {
    Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::get('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'show']);
    Route::patch('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'update']);

    Route::middleware('idempotent')->group(function () {
        Route::post('purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit']);
        Route::post('purchase-orders/{purchase_order}/resubmit', [PurchaseOrderController::class, 'resubmit']);
        Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel']);
    });

    Route::get('approvals/inbox', [ApprovalsController::class, 'inbox']);
    Route::get('approvals/processes/{process}', [ApprovalsController::class, 'showProcess']);

    Route::middleware('idempotent')->group(function () {
        Route::post('approvals/step-instances/{step_instance}/approve', [ApprovalsController::class, 'approve']);
        Route::post('approvals/step-instances/{step_instance}/reject', [ApprovalsController::class, 'reject']);
    });

    Route::get('workflows', [WorkflowsController::class, 'index']);
    Route::post('workflows', [WorkflowsController::class, 'store']);
    Route::get('workflows/{workflow}', [WorkflowsController::class, 'show']);
    Route::post('workflows/{workflow}/versions', [WorkflowsController::class, 'storeVersion']);
    Route::get('workflow-versions/{workflow_version}', [WorkflowsController::class, 'showVersion']);
    Route::post('workflow-versions/{workflow_version}/publish', [WorkflowsController::class, 'publishVersion']);

    Route::middleware('idempotent')->group(function () {
        Route::post('admin/processes/{process}/inject-step', [AdminProcessController::class, 'injectStep']);
        Route::post('admin/processes/{process}/cancel-and-restart', [AdminProcessController::class, 'cancelAndRestart']);
    });
});
