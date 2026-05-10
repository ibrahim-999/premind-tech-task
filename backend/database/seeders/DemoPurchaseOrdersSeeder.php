<?php

namespace Database\Seeders;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\User\Models\User;
use App\Domains\Workflow\Engine\WorkflowEngine;
use Illuminate\Database\Seeder;

class DemoPurchaseOrdersSeeder extends Seeder
{
    public function __construct(private readonly WorkflowEngine $engine) {}

    public function run(): void
    {
        if (PurchaseOrder::query()->exists()) {
            return;
        }

        $ali = User::where('email', 'ali.dev@premind.local')->firstOrFail();
        $omar = User::where('email', 'omar.it@premind.local')->firstOrFail();

        $this->createSubmittedPO(
            $ali,
            ['title' => '2x MacBook Pro 16"', 'category' => 'IT'],
            [['name' => 'MacBook Pro 16"', 'quantity' => 2, 'unit_price' => 4000]],
        );

        $this->createSubmittedPO(
            $omar,
            ['title' => 'Office Printer', 'category' => 'IT'],
            [['name' => 'Brother MFC L8900', 'quantity' => 1, 'unit_price' => 1500]],
        );
    }

    private function createSubmittedPO(User $requester, array $base, array $items): void
    {
        $po = PurchaseOrder::create([
            'requester_id' => $requester->getKey(),
            'title' => $base['title'],
            'description' => $base['description'] ?? null,
            'category' => $base['category'],
            'department_id' => $requester->department_id,
        ]);

        foreach ($items as $item) {
            $po->items()->create($item);
        }

        $po->load('items')->recalculateAmount()->save();
        $po->fresh('items')->submit($this->engine);
    }
}
