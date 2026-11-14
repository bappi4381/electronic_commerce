<?php

namespace App\Notifications\Admin;

use App\Models\Order;
use App\Services\Fraud\RiskResult;

class RiskyOrderFlagged extends AdminNotification
{
    public function __construct(Order $order, RiskResult $result, bool $held)
    {
        $this->data = [
            'kind'  => 'order',
            'title' => 'High-risk order ' . $order->order_id . ($held ? ' put on hold' : ''),
            'body'  => sprintf('%s%% delivered (%d of %d), %d cancelled · Tk %s', $result->successRate ?? 0, $result->delivered, $result->total, $result->cancelled, number_format((float) $order->total_price, 2)),
            'url'   => route('orders.show', $order, false),
            'icon'  => 'bi-shield-exclamation',
            'color' => 'red',
        ];
    }
}
