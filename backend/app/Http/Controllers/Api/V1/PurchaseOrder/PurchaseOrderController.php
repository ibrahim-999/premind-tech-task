<?php

namespace App\Http\Controllers\Api\V1\PurchaseOrder;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Domains\Workflow\Engine\WorkflowEngine;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\Api\V1\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Http\Resources\Api\V1\PurchaseOrder\PurchaseOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly PurchaseOrderService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = PurchaseOrder::query()->with(['requester', 'items']);

        if (! $user->hasRole('admin')) {
            $query->where('requester_id', $user->getKey());
        }

        return PurchaseOrderResource::collection($query->latest()->paginate(20));
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $po = $this->service->createForRequester($request->user(), $request->validated());

        return PurchaseOrderResource::make($po)->response()->setStatusCode(201);
    }

    public function show(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $this->authorize('view', $purchaseOrder);

        return PurchaseOrderResource::make($purchaseOrder->load(['requester', 'items']));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        return PurchaseOrderResource::make(
            $this->service->update($purchaseOrder, $request->validated())
        );
    }

    public function submit(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('submit', $purchaseOrder);

        $purchaseOrder->load('items')->submit($this->engine);

        return PurchaseOrderResource::make($purchaseOrder->fresh(['requester', 'items']))->response();
    }

    public function resubmit(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('resubmit', $purchaseOrder);

        $purchaseOrder->load('items')->resubmit($this->engine);

        return PurchaseOrderResource::make($purchaseOrder->fresh(['requester', 'items']))->response();
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('cancel', $purchaseOrder);

        $purchaseOrder->cancel($this->engine, $request->user(), $request->input('reason'));

        return PurchaseOrderResource::make($purchaseOrder->fresh(['requester', 'items']))->response();
    }
}
