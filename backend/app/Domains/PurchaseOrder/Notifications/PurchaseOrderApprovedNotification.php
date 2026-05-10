<?php

namespace App\Domains\PurchaseOrder\Notifications;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderApprovedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public readonly PurchaseOrder $purchaseOrder) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your purchase order was approved')
            ->line(sprintf('Your purchase order "%s" has been approved.', $this->purchaseOrder->title))
            ->line(sprintf('Amount: %s', $this->purchaseOrder->amount));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'purchase_order_approved',
            'purchase_order_id' => $this->purchaseOrder->id,
            'title' => $this->purchaseOrder->title,
            'amount' => (string) $this->purchaseOrder->amount,
        ];
    }
}
