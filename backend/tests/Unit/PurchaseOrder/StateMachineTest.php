<?php

namespace Tests\Unit\PurchaseOrder;

use App\Domains\PurchaseOrder\Enums\PurchaseOrderStatus;
use App\Domains\PurchaseOrder\Exceptions\IllegalStateTransition;
use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_approved_succeeds_from_submitted(): void
    {
        $po = $this->purchaseOrderInState(PurchaseOrderStatus::Submitted);

        $po->markApproved();

        $fresh = $po->fresh();
        $this->assertSame(PurchaseOrderStatus::Approved, $fresh->status);
        $this->assertNotNull($fresh->approved_at);
    }

    #[DataProvider('nonSubmittedStates')]
    public function test_mark_approved_throws_from_invalid_state(PurchaseOrderStatus $from): void
    {
        $po = $this->purchaseOrderInState($from);

        $this->expectException(IllegalStateTransition::class);
        $po->markApproved();
    }

    public function test_mark_rejected_succeeds_from_submitted_and_records_reason(): void
    {
        $po = $this->purchaseOrderInState(PurchaseOrderStatus::Submitted);

        $po->markRejected('quote missing breakdown');

        $fresh = $po->fresh();
        $this->assertSame(PurchaseOrderStatus::Rejected, $fresh->status);
        $this->assertSame('quote missing breakdown', $fresh->last_rejection_reason);
        $this->assertNotNull($fresh->rejected_at);
    }

    #[DataProvider('nonSubmittedStates')]
    public function test_mark_rejected_throws_from_invalid_state(PurchaseOrderStatus $from): void
    {
        $po = $this->purchaseOrderInState($from);

        $this->expectException(IllegalStateTransition::class);
        $po->markRejected('reason');
    }

    #[DataProvider('cancellableStates')]
    public function test_mark_cancelled_succeeds_from_cancellable_state(PurchaseOrderStatus $from): void
    {
        $po = $this->purchaseOrderInState($from);

        $po->markCancelled();

        $fresh = $po->fresh();
        $this->assertSame(PurchaseOrderStatus::Cancelled, $fresh->status);
        $this->assertNotNull($fresh->cancelled_at);
    }

    #[DataProvider('terminalStates')]
    public function test_mark_cancelled_throws_from_terminal_state(PurchaseOrderStatus $from): void
    {
        $po = $this->purchaseOrderInState($from);

        $this->expectException(IllegalStateTransition::class);
        $po->markCancelled();
    }

    public function test_is_terminal_helper_distinguishes_approved_and_cancelled(): void
    {
        $this->assertFalse(PurchaseOrderStatus::Draft->isTerminal());
        $this->assertFalse(PurchaseOrderStatus::Submitted->isTerminal());
        $this->assertFalse(PurchaseOrderStatus::Rejected->isTerminal());
        $this->assertTrue(PurchaseOrderStatus::Approved->isTerminal());
        $this->assertTrue(PurchaseOrderStatus::Cancelled->isTerminal());
    }

    public function test_is_editable_helper_allows_only_draft_and_rejected(): void
    {
        $this->assertTrue(PurchaseOrderStatus::Draft->isEditable());
        $this->assertTrue(PurchaseOrderStatus::Rejected->isEditable());
        $this->assertFalse(PurchaseOrderStatus::Submitted->isEditable());
        $this->assertFalse(PurchaseOrderStatus::Approved->isEditable());
        $this->assertFalse(PurchaseOrderStatus::Cancelled->isEditable());
    }

    public function test_is_cancellable_helper_excludes_terminal_states(): void
    {
        $this->assertTrue(PurchaseOrderStatus::Draft->isCancellable());
        $this->assertTrue(PurchaseOrderStatus::Submitted->isCancellable());
        $this->assertTrue(PurchaseOrderStatus::Rejected->isCancellable());
        $this->assertFalse(PurchaseOrderStatus::Approved->isCancellable());
        $this->assertFalse(PurchaseOrderStatus::Cancelled->isCancellable());
    }

    public static function nonSubmittedStates(): array
    {
        return [
            'draft' => [PurchaseOrderStatus::Draft],
            'approved' => [PurchaseOrderStatus::Approved],
            'rejected' => [PurchaseOrderStatus::Rejected],
            'cancelled' => [PurchaseOrderStatus::Cancelled],
        ];
    }

    public static function cancellableStates(): array
    {
        return [
            'draft' => [PurchaseOrderStatus::Draft],
            'submitted' => [PurchaseOrderStatus::Submitted],
            'rejected' => [PurchaseOrderStatus::Rejected],
        ];
    }

    public static function terminalStates(): array
    {
        return [
            'approved' => [PurchaseOrderStatus::Approved],
            'cancelled' => [PurchaseOrderStatus::Cancelled],
        ];
    }

    private function purchaseOrderInState(PurchaseOrderStatus $status): PurchaseOrder
    {
        $requester = User::factory()->create();

        $po = PurchaseOrder::create([
            'requester_id' => $requester->id,
            'title' => 'Test PO',
            'category' => 'IT',
            'amount' => 100,
        ]);

        $po->forceFill(['status' => $status])->save();

        return $po->fresh();
    }
}
