<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $paymentMethod;

    public function __construct(Order $order, $paymentMethod = 'cod')
    {
        // Eager-load order items so serialization works
        $this->order = $order->load('orderItems');
        $this->paymentMethod = $paymentMethod;
    }

    public function build()
    {
        return $this->subject("Order {$this->order->order_id} confirmation - " . config('app.name'))
                    ->markdown('emails.order_confirmation');
    }
}
