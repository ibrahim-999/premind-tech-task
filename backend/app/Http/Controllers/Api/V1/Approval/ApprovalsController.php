<?php

namespace App\Http\Controllers\Api\V1\Approval;

use App\Domains\Workflow\Engine\WorkflowEngine;
use App\Domains\Workflow\Enums\ActionType;
use App\Domains\Workflow\Models\ApprovalProcess;
use App\Domains\Workflow\Models\ApprovalStepInstance;
use App\Domains\Workflow\Services\InboxQueryService;
use App\Domains\Workflow\Services\ProcessLoader;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Approval\ApproveStepRequest;
use App\Http\Requests\Api\V1\Approval\RejectStepRequest;
use App\Http\Resources\Api\V1\Approval\ApprovalProcessResource;
use App\Http\Resources\Api\V1\Approval\InboxItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalsController extends Controller
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly InboxQueryService $inbox,
        private readonly ProcessLoader $loader,
    ) {}

    public function inbox(Request $request): JsonResponse
    {
        $page = $this->inbox->paginatedFor($request->user());

        return response()->json([
            'data' => InboxItemResource::collection($page->items())->resolve(),
            'meta' => [
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => $page->previousCursor()?->encode(),
                'per_page' => $page->perPage(),
            ],
        ]);
    }

    public function showProcess(ApprovalProcess $process): ApprovalProcessResource
    {
        $this->authorize('viewProcess', $process);

        return ApprovalProcessResource::make($this->loader->full($process));
    }

    public function approve(ApproveStepRequest $request, ApprovalStepInstance $stepInstance): JsonResponse
    {
        $this->authorize('act', $stepInstance);

        $this->engine->submitAction(
            $stepInstance,
            $request->user(),
            ActionType::Approve,
            $request->input('comment'),
            $request->header('Idempotency-Key'),
        );

        return ApprovalProcessResource::make($this->loader->full($stepInstance->process))->response();
    }

    public function reject(RejectStepRequest $request, ApprovalStepInstance $stepInstance): JsonResponse
    {
        $this->authorize('act', $stepInstance);

        $this->engine->submitAction(
            $stepInstance,
            $request->user(),
            ActionType::Reject,
            $request->validated('reason'),
            $request->header('Idempotency-Key'),
        );

        return ApprovalProcessResource::make($this->loader->full($stepInstance->process))->response();
    }
}
