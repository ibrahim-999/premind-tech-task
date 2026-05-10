<?php

namespace App\Domains\PurchaseOrder\Listeners;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\PurchaseOrder\Notifications\PurchaseOrderApprovedNotification;
use App\Domains\PurchaseOrder\Notifications\PurchaseOrderRejectedNotification;
use App\Domains\PurchaseOrder\Notifications\StepAssignedNotification;
use App\Domains\Workflow\Events\ProcessApproved;
use App\Domains\Workflow\Events\ProcessRejected;
use App\Domains\Workflow\Events\StepEntered;
use Illuminate\Events\Dispatcher;

class NotifyParticipants
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            StepEntered::class => 'onStepEntered',
            ProcessApproved::class => 'onProcessApproved',
            ProcessRejected::class => 'onProcessRejected',
        ];
    }

    public function onStepEntered(StepEntered $event): void
    {
        $stepInstance = $event->stepInstance;
        $process = $stepInstance->process;
        $subject = $process?->subject;

        if (! $subject instanceof PurchaseOrder) {
            return;
        }

        foreach ($stepInstance->assignees as $assignee) {
            $recipient = $assignee->user;

            if ($recipient !== null) {
                $recipient->notify(new StepAssignedNotification($subject, $stepInstance));
            }
        }
    }

    public function onProcessApproved(ProcessApproved $event): void
    {
        if (! $event->subject instanceof PurchaseOrder) {
            return;
        }

        $event->subject->requester?->notify(new PurchaseOrderApprovedNotification($event->subject));
    }

    public function onProcessRejected(ProcessRejected $event): void
    {
        if (! $event->subject instanceof PurchaseOrder) {
            return;
        }

        $event->subject->requester?->notify(new PurchaseOrderRejectedNotification($event->subject, $event->reason));
    }
}
