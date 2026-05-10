<?php

namespace App\Domains\PurchaseOrder\Notifications;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderRejectedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
        public readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your purchase order was rejected')
            ->line(sprintf('Your purchase order "%s" was rejected.', $this->purchaseOrder->title))
            ->line(sprintf('Reason: %s', $this->reason))
            ->line('You can edit and resubmit it from your dashboard.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'purchase_order_rejected',
            'purchase_order_id' => $this->purchaseOrder->id,
            'title' => $this->purchaseOrder->title,
            'reason' => $this->reason,
        ];
    }
}
