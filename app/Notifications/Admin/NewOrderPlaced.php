<?php

namespace App\Notifications\Admin;

use App\Models\Order;

class NewOrderPlaced extends AdminNotification
{
    public function __construct(Order $order)
    {
        $this->data = [
            'kind'  => 'order',
            'title' => 'New order ' . $order->order_id,
            'body'  => $order->customerDisplayName() . ' · Tk ' . number_format((float) $order->total_price, 2) . ' · ' . strtoupper((string) $order->payment_method),
            'url'   => route('orders.show', $order, false),
            'icon'  => 'bi-bag-check-fill',
            'color' => 'emerald',
        ];
    }
}
