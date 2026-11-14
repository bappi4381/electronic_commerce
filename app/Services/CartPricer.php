<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Re-reads every cart line from the catalog, so the customer always sees (and pays) today's price:
 * prices that changed are updated, unavailable items are removed, quantities are capped at stock.
 */
class CartPricer
{
    /**
     * @return array{cart: array, changes: list<string>}
     */
    public function refresh(array $cart): array
    {
        if (!$cart) {
            return ['cart' => [], 'changes' => []];
        }

        $products = Product::with('variants')->whereIn('id', collect($cart)->pluck('product_id')->filter())->get()->keyBy('id');
        $variants = ProductVariant::whereIn('id', collect($cart)->pluck('variant_id')->filter())->get()->keyBy('id');
        $changes = [];

        foreach ($cart as $key => $item) {
            $name = $item['name'] . (!empty($item['variant_name']) ? ' (' . $item['variant_name'] . ')' : '');
            $product = $products->get($item['product_id'] ?? null);
            $variant = !empty($item['variant_id']) ? $variants->get($item['variant_id']) : null;

            if (!$product || (!empty($item['variant_id']) && (!$variant || !$variant->is_active || (int) $variant->product_id !== (int) $product->id))) {
                unset($cart[$key]);
                $changes[] = __(':name is no longer available and was removed from your cart.', ['name' => $name]);
                continue;
            }

            $variant?->setRelation('product', $product);
            $price = $variant ? $variant->unitPrice() : $product->unitPrice();
            $stock = $variant ? (int) $variant->stock : (int) $product->stock;

            if ($stock <= 0) {
                unset($cart[$key]);
                $changes[] = __(':name is sold out and was removed from your cart.', ['name' => $name]);
                continue;
            }

            if (round($price, 2) !== round((float) $item['price'], 2)) {
                $changes[] = __('The price of :name changed from :old to :new.', ['name' => $name, 'old' => money((float) $item['price']), 'new' => money($price)]);
                $cart[$key]['price'] = $price;
            }

            if ((int) $item['quantity'] > $stock) {
                $changes[] = __('Only :count of :name left, so the quantity was reduced.', ['count' => $stock, 'name' => $name]);
                $cart[$key]['quantity'] = $stock;
            }

            $cart[$key]['stock'] = $stock;
        }

        return ['cart' => $cart, 'changes' => $changes];
    }

    /** Refresh the session cart; returns what changed. */
    public function refreshSession(): array
    {
        ['cart' => $cart, 'changes' => $changes] = $this->refresh(session('cart', []));
        session(['cart' => $cart]);

        return $changes;
    }
}
