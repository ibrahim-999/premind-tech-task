<?php

namespace App\Domains\PurchaseOrder;

use App\Domains\PurchaseOrder\Listeners\NotifyParticipants;
use App\Domains\PurchaseOrder\Listeners\SyncPurchaseOrderStatus;
use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class PurchaseOrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(Dispatcher $events): void
    {
        Relation::enforceMorphMap([
            'purchase_order' => PurchaseOrder::class,
        ]);

        $events->subscribe(SyncPurchaseOrderStatus::class);
        $events->subscribe(NotifyParticipants::class);
    }
}
