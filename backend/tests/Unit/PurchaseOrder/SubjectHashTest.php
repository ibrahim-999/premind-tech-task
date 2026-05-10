<?php

namespace Tests\Unit\PurchaseOrder;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectHashTest extends TestCase
{
    use RefreshDatabase;

    public function test_hash_is_deterministic_for_unchanged_content(): void
    {
        $po = $this->purchaseOrder([
            'title' => 'Office Printer',
            'description' => 'Replacement unit',
            'category' => 'IT',
            'department_id' => 3,
            'amount' => 1500,
        ], [
            ['name' => 'Brother MFC L8900', 'quantity' => 1, 'unit_price' => 1500],
        ]);

        $first = $po->computeSubjectHash();
        $second = $po->fresh()->computeSubjectHash();

        $this->assertSame($first, $second);
        $this->assertSame(64, strlen($first));
    }

    public function test_hash_changes_when_title_changes(): void
    {
        $po = $this->purchaseOrder(['title' => 'Original'], [
            ['name' => 'A', 'quantity' => 1, 'unit_price' => 100],
        ]);
        $before = $po->computeSubjectHash();

        $po->title = 'Edited';

        $this->assertNotSame($before, $po->computeSubjectHash());
    }

    public function test_hash_changes_when_amount_changes(): void
    {
        $po = $this->purchaseOrder(['amount' => 100], [
            ['name' => 'A', 'quantity' => 1, 'unit_price' => 100],
        ]);
        $before = $po->computeSubjectHash();

        $po->amount = 200;

        $this->assertNotSame($before, $po->computeSubjectHash());
    }

    public function test_hash_changes_when_item_quantity_changes(): void
    {
        $po = $this->purchaseOrder([], [
            ['name' => 'Widget', 'quantity' => 1, 'unit_price' => 100],
        ]);
        $before = $po->computeSubjectHash();

        $po->items()->first()->update(['quantity' => 2]);

        $this->assertNotSame($before, $po->fresh()->computeSubjectHash());
    }

    public function test_hash_changes_when_item_name_changes(): void
    {
        $po = $this->purchaseOrder([], [
            ['name' => 'Original', 'quantity' => 1, 'unit_price' => 100],
        ]);
        $before = $po->computeSubjectHash();

        $po->items()->first()->update(['name' => 'Renamed']);

        $this->assertNotSame($before, $po->fresh()->computeSubjectHash());
    }

    public function test_hash_changes_when_item_unit_price_changes(): void
    {
        $po = $this->purchaseOrder([], [
            ['name' => 'Widget', 'quantity' => 1, 'unit_price' => 100],
        ]);
        $before = $po->computeSubjectHash();

        $po->items()->first()->update(['unit_price' => 150]);

        $this->assertNotSame($before, $po->fresh()->computeSubjectHash());
    }

    public function test_hash_changes_when_item_added(): void
    {
        $po = $this->purchaseOrder([], [
            ['name' => 'Widget', 'quantity' => 1, 'unit_price' => 100],
        ]);
        $before = $po->computeSubjectHash();

        $po->items()->create(['name' => 'Extra', 'quantity' => 1, 'unit_price' => 50]);

        $this->assertNotSame($before, $po->fresh()->computeSubjectHash());
    }

    private function purchaseOrder(array $overrides = [], array $items = []): PurchaseOrder
    {
        $requester = User::factory()->create();

        $po = PurchaseOrder::create(array_merge([
            'requester_id' => $requester->id,
            'title' => 'Test PO',
            'category' => 'IT',
            'department_id' => 1,
            'amount' => 0,
        ], $overrides));

        foreach ($items as $item) {
            $po->items()->create($item);
        }

        return $po->fresh('items');
    }
}
