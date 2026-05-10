<?php

namespace App\Domains\PurchaseOrder\Services;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\User\Models\User;
use App\Domains\Workflow\Engine\WorkflowEngine;
use App\Domains\Workflow\Models\ApprovalProcess;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(private readonly WorkflowEngine $engine) {}

    public function createForRequester(User $requester, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($requester, $data) {
            $po = PurchaseOrder::create([
                'requester_id' => $requester->getKey(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'],
                'department_id' => $data['department_id'] ?? null,
            ]);

            $this->replaceItems($po, $data['items']);

            return $po->fresh(['items', 'requester']);
        });
    }

    public function update(PurchaseOrder $po, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data) {
            $po->fill(array_diff_key($data, ['items' => null]))->save();

            if (array_key_exists('items', $data)) {
                $po->items()->delete();
                $this->replaceItems($po, $data['items']);
            } else {
                $po->load('items')->recalculateAmount()->save();
            }

            return $po->fresh(['items', 'requester']);
        });
    }

    public function cancelAndRestart(PurchaseOrder $po, User $admin, ?string $reason): ApprovalProcess
    {
        return DB::transaction(function () use ($po, $admin, $reason) {
            $active = $po->activeProcess();

            if ($active !== null) {
                $this->engine->cancel($active, $admin, $reason);
            }

            return $po->fresh(['items'])->submit($this->engine);
        });
    }

    private function replaceItems(PurchaseOrder $po, array $items): void
    {
        foreach ($items as $item) {
            $po->items()->create($item);
        }

        $po->load('items')->recalculateAmount()->save();
    }
}
