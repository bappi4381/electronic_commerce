<?php

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $status, public ?string $note = null)
    {
        $this->afterCommit();
    }

    public function build()
    {
        $status = OrderStatus::from($this->status);

        return $this->subject("Order {$this->order->order_id} is {$status->label()} - " . config('app.name'))
            ->markdown('emails.order_status_changed', [
                'statusLabel' => $status->label(),
                'message' => $status->customerMessage(),
                'courier' => $this->order->orderCourier,
            ]);
    }
}
