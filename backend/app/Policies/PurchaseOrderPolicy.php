<?php

namespace App\Policies;

use App\Domains\PurchaseOrder\Enums\PurchaseOrderStatus;
use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\User\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseOrder $po): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ((int) $po->requester_id === (int) $user->getKey()) {
            return true;
        }

        return $po->approvalProcesses()
            ->whereHas('stepInstances.assignees', fn ($q) => $q->where('user_id', $user->getKey()))
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PurchaseOrder $po): bool
    {
        if ((int) $po->requester_id !== (int) $user->getKey()) {
            return false;
        }

        return $po->status->isEditable();
    }

    public function submit(User $user, PurchaseOrder $po): bool
    {
        return (int) $po->requester_id === (int) $user->getKey()
            && $po->status === PurchaseOrderStatus::Draft;
    }

    public function resubmit(User $user, PurchaseOrder $po): bool
    {
        return (int) $po->requester_id === (int) $user->getKey()
            && $po->status === PurchaseOrderStatus::Rejected;
    }

    public function cancel(User $user, PurchaseOrder $po): bool
    {
        if (! $po->status->isCancellable()) {
            return false;
        }

        return $user->hasRole('admin') || (int) $po->requester_id === (int) $user->getKey();
    }
}
