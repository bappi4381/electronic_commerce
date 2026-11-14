<?php

namespace App\Notifications\Admin;

use App\Models\Order;

class RiskCheckFailed extends AdminNotification
{
    public function __construct(Order $order, string $reason)
    {
        $this->data = [
            'kind'  => 'order',
            'title' => 'Risk check failed for ' . $order->order_id,
            'body'  => \Illuminate\Support\Str::limit($reason ?: 'Unknown error', 140) . ' The order was not blocked; review it manually.',
            'url'   => route('orders.show', $order, false),
            'icon'  => 'bi-question-octagon',
            'color' => 'amber',
        ];
    }
}
