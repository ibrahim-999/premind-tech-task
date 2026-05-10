<?php

namespace App\Domains\PurchaseOrder\Listeners;

use App\Domains\PurchaseOrder\Enums\PurchaseOrderStatus;
use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\Workflow\Events\ProcessApproved;
use App\Domains\Workflow\Events\ProcessCancelled;
use App\Domains\Workflow\Events\ProcessRejected;
use Illuminate\Events\Dispatcher;

class SyncPurchaseOrderStatus
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            ProcessApproved::class => 'onApproved',
            ProcessRejected::class => 'onRejected',
            ProcessCancelled::class => 'onCancelled',
        ];
    }

    public function onApproved(ProcessApproved $event): void
    {
        $po = $this->resolvePurchaseOrder($event->subject);

        if ($po === null || $po->status !== PurchaseOrderStatus::Submitted) {
            return;
        }

        $po->markApproved();
    }

    public function onRejected(ProcessRejected $event): void
    {
        $po = $this->resolvePurchaseOrder($event->subject);

        if ($po === null || $po->status !== PurchaseOrderStatus::Submitted) {
            return;
        }

        $po->markRejected($event->reason);
    }

    public function onCancelled(ProcessCancelled $event): void
    {
        $po = $this->resolvePurchaseOrder($event->subject);

        if ($po === null || $po->status === PurchaseOrderStatus::Cancelled) {
            return;
        }

        $po->markCancelled();
    }

    private function resolvePurchaseOrder(mixed $subject): ?PurchaseOrder
    {
        return $subject instanceof PurchaseOrder ? $subject : null;
    }
}
