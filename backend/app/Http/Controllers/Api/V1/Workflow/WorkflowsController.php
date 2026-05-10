<?php

namespace App\Http\Controllers\Api\V1\Workflow;

use App\Domains\Workflow\Models\Workflow;
use App\Domains\Workflow\Models\WorkflowVersion;
use App\Domains\Workflow\Services\WorkflowDefinitionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workflow\StoreWorkflowRequest;
use App\Http\Requests\Api\V1\Workflow\StoreWorkflowVersionRequest;
use App\Http\Resources\Api\V1\Workflow\WorkflowResource;
use App\Http\Resources\Api\V1\Workflow\WorkflowVersionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkflowsController extends Controller
{
    public function __construct(private readonly WorkflowDefinitionService $service) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Workflow::class);

        return WorkflowResource::collection(
            Workflow::query()->with('versions')->orderBy('name')->paginate(20)
        );
    }

    public function show(Workflow $workflow): WorkflowResource
    {
        $this->authorize('view', $workflow);

        return WorkflowResource::make(
            $workflow->load('versions.steps.conditions', 'versions.steps.approvers')
        );
    }

    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::create($request->validated());

        return WorkflowResource::make($workflow)->response()->setStatusCode(201);
    }

    public function storeVersion(StoreWorkflowVersionRequest $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $version = $this->service->createVersion($workflow, $request->validated('steps'));

        return WorkflowVersionResource::make($version)->response()->setStatusCode(201);
    }

    public function showVersion(WorkflowVersion $workflowVersion): WorkflowVersionResource
    {
        $this->authorize('view', $workflowVersion);

        return WorkflowVersionResource::make(
            $workflowVersion->load('steps.conditions', 'steps.approvers')
        );
    }

    public function publishVersion(Request $request, WorkflowVersion $workflowVersion): JsonResponse
    {
        $this->authorize('publish', $workflowVersion);

        $version = $this->service->publish($workflowVersion, $request->user());

        return WorkflowVersionResource::make($version)->response();
    }
}
