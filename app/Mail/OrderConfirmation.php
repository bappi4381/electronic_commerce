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
    public $password;
    public $paymentMethod;

    public function __construct(Order $order, $password = null, $paymentMethod = 'cod')
    {
        // Eager-load order items so serialization works
        $this->order = $order->load('orderItems');
        $this->password = $password;
        $this->paymentMethod = $paymentMethod;
    }

    public function build()
    {
        return $this->subject('Order Confirmation - BookSaw')
                    ->markdown('emails.order_confirmation');
    }
}
