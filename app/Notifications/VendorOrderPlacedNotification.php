<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorOrderPlacedNotification extends Notification
{
    use Queueable;

    /**
     * @param array<int, array<string, mixed>> $vendorItems
     */
    public function __construct(
        private readonly Order $order,
        private readonly array $vendorItems,
        private readonly string $customerName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Stone | New Order in Your Store - ' . $this->order->order_number)
            ->view('emails.vendor-order-placed', [
                'vendorName' => $notifiable->name,
                'order' => $this->order,
                'vendorItems' => $this->vendorItems,
                'customerName' => $this->customerName,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'طلب جديد',
            'message' => 'تم إنشاء طلب جديد يحتوي على منتجاتك.',
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'total' => (float) $this->order->total,
            'customer_name' => $this->customerName,
            'items_count' => count($this->vendorItems),
            'items' => $this->vendorItems,
        ];
    }
}
