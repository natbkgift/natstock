<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InventoryAlertNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'สรุปแจ้งเตือนสินค้าประจำวัน',
            'payload' => $this->payload,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
