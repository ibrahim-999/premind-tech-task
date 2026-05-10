<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Domains\Workflow\Engine\WorkflowEngine;
use App\Domains\Workflow\Models\ApprovalProcess;
use App\Domains\Workflow\Services\ProcessLoader;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\InjectStepRequest;
use App\Http\Resources\Api\V1\Approval\ApprovalProcessResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProcessController extends Controller
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly PurchaseOrderService $poService,
        private readonly ProcessLoader $loader,
    ) {}

    public function injectStep(InjectStepRequest $request, ApprovalProcess $process): JsonResponse
    {
        $this->engine->injectAdHocStep(
            $process,
            $request->user(),
            $request->validated('name'),
            $request->validated('resolver_type'),
            $request->validated('config') ?? [],
            $request->validated('reason'),
        );

        return ApprovalProcessResource::make($this->loader->full($process))->response();
    }

    public function cancelAndRestart(Request $request, ApprovalProcess $process): JsonResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $subject = $process->subject;

        if (! $subject instanceof PurchaseOrder) {
            return response()->json([
                'error' => 'unsupported_subject',
                'message' => 'cancel-and-restart is only implemented for PurchaseOrder subjects.',
            ], 400);
        }

        $newProcess = $this->poService->cancelAndRestart(
            $subject,
            $request->user(),
            $request->input('reason'),
        );

        return ApprovalProcessResource::make($this->loader->full($newProcess))
            ->response()
            ->setStatusCode(201);
    }
}
