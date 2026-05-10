<?php

namespace App\Domains\PurchaseOrder\Models;

use App\Domains\User\Models\User;
use App\Domains\PurchaseOrder\Enums\PurchaseOrderStatus;
use App\Domains\PurchaseOrder\Exceptions\IllegalStateTransition;
use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Engine\WorkflowEngine;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Models\ApprovalProcess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model implements Approvable
{
    protected $fillable = [
        'requester_id',
        'title',
        'description',
        'category',
        'department_id',
        'amount',
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'last_rejection_reason',
        'submission_count',
        'subject_hash',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PurchaseOrderStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'submission_count' => 'integer',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function approvalProcesses(): MorphMany
    {
        return $this->morphMany(ApprovalProcess::class, 'subject');
    }

    public function activeProcess(): ?ApprovalProcess
    {
        return $this->approvalProcesses()
            ->where('status', ProcessStatus::Pending->value)
            ->first();
    }

    public function approvalAmount(): ?float
    {
        return (float) $this->amount;
    }

    public function approvalAttributes(): array
    {
        return [
            'category' => $this->category,
            'department_id' => $this->department_id,
            'amount' => (float) $this->amount,
            'title' => $this->title,
        ];
    }

    public function approvalSubmitter(): User
    {
        return $this->requester;
    }

    public function recalculateAmount(): self
    {
        $total = $this->items()->get()->sum(fn ($i) => $i->lineTotal());
        $this->amount = round($total, 2);

        return $this;
    }

    public function computeSubjectHash(): string
    {
        $payload = [
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'department_id' => $this->department_id,
            'amount' => (string) $this->amount,
            'items' => $this->items()
                ->orderBy('id')
                ->get()
                ->map(fn ($i) => [
                    'name' => $i->name,
                    'quantity' => (int) $i->quantity,
                    'unit_price' => (string) $i->unit_price,
                ])
                ->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function submit(WorkflowEngine $engine): ApprovalProcess
    {
        $this->assertCanTransitionTo(PurchaseOrderStatus::Submitted);

        return DB::transaction(function () use ($engine) {
            $this->recalculateAmount();
            $this->forceFill([
                'status' => PurchaseOrderStatus::Submitted,
                'submitted_at' => now(),
                'submission_count' => $this->submission_count + 1,
                'subject_hash' => $this->computeSubjectHash(),
                'last_rejection_reason' => null,
            ])->save();

            return $engine->start($this->fresh(['items']));
        });
    }

    public function resubmit(WorkflowEngine $engine): ApprovalProcess
    {
        if ($this->status !== PurchaseOrderStatus::Rejected) {
            throw new IllegalStateTransition($this->status, PurchaseOrderStatus::Submitted);
        }

        return $this->submit($engine);
    }

    public function cancel(WorkflowEngine $engine, ?User $actor = null, ?string $reason = null): self
    {
        $this->assertCanTransitionTo(PurchaseOrderStatus::Cancelled);

        return DB::transaction(function () use ($engine, $actor, $reason) {
            $process = $this->activeProcess();

            if ($process !== null) {
                $engine->cancel($process, $actor, $reason);

                return $this->fresh();
            }

            $this->forceFill([
                'status' => PurchaseOrderStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            return $this;
        });
    }

    public function markApproved(): void
    {
        $this->assertCanTransitionTo(PurchaseOrderStatus::Approved);

        $this->forceFill([
            'status' => PurchaseOrderStatus::Approved,
            'approved_at' => now(),
        ])->save();
    }

    public function markRejected(string $reason): void
    {
        $this->assertCanTransitionTo(PurchaseOrderStatus::Rejected);

        $this->forceFill([
            'status' => PurchaseOrderStatus::Rejected,
            'rejected_at' => now(),
            'last_rejection_reason' => $reason,
        ])->save();
    }

    public function markCancelled(): void
    {
        $this->assertCanTransitionTo(PurchaseOrderStatus::Cancelled);

        $this->forceFill([
            'status' => PurchaseOrderStatus::Cancelled,
            'cancelled_at' => now(),
        ])->save();
    }

    private function assertCanTransitionTo(PurchaseOrderStatus $target): void
    {
        if (! $this->canTransitionTo($target)) {
            throw new IllegalStateTransition($this->status, $target);
        }
    }

    private function canTransitionTo(PurchaseOrderStatus $target): bool
    {
        return match ([$this->status, $target]) {
            [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Submitted] => true,
            [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Cancelled] => true,
            [PurchaseOrderStatus::Submitted, PurchaseOrderStatus::Approved] => true,
            [PurchaseOrderStatus::Submitted, PurchaseOrderStatus::Rejected] => true,
            [PurchaseOrderStatus::Submitted, PurchaseOrderStatus::Cancelled] => true,
            [PurchaseOrderStatus::Rejected, PurchaseOrderStatus::Submitted] => true,
            [PurchaseOrderStatus::Rejected, PurchaseOrderStatus::Cancelled] => true,
            default => false,
        };
    }
}
