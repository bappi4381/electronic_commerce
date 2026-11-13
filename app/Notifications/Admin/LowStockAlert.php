<?php

namespace App\Notifications\Admin;

use App\Models\ProductVariant;

class LowStockAlert extends AdminNotification
{
    public function __construct(ProductVariant $variant, int $stock, int $threshold)
    {
        $this->data = [
            'kind'  => 'stock',
            'title' => $stock <= 0 ? 'Out of stock' : 'Low stock',
            'body'  => $variant->label() . ' · ' . $stock . ' left (threshold ' . $threshold . ')',
            'url'   => route('admin.products.edit', $variant->product_id, false),
            'icon'  => $stock <= 0 ? 'bi-x-octagon-fill' : 'bi-exclamation-triangle-fill',
            'color' => $stock <= 0 ? 'red' : 'amber',
        ];
    }
}
