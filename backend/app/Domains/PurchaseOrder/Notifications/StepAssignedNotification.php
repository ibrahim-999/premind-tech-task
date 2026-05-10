<?php

namespace App\Domains\PurchaseOrder\Notifications;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use App\Domains\Workflow\Models\ApprovalStepInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StepAssignedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
        public readonly ApprovalStepInstance $stepInstance,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('A purchase order is awaiting your approval')
            ->line(sprintf('"%s" is waiting for you on the "%s" step.', $this->purchaseOrder->title, $this->stepInstance->displayName()))
            ->line(sprintf('Amount: %s', $this->purchaseOrder->amount))
            ->action('Review', url(sprintf('/processes/%d', $this->stepInstance->approval_process_id)));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'step_assigned',
            'purchase_order_id' => $this->purchaseOrder->id,
            'title' => $this->purchaseOrder->title,
            'step_instance_id' => $this->stepInstance->id,
            'step_name' => $this->stepInstance->displayName(),
        ];
    }
}
