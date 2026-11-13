<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Every report returns the same shape so the view and the exports can render it generically:
 * ['columns' => [key => label], 'rows' => list<array>, 'summary' => [label => value], 'chart' => ?array].
 */
class ReportService
{
    public const REPORTS = [
        'sales'     => 'Sales by date',
        'products'  => 'Top products',
        'stock'     => 'Low stock',
        'customers' => 'Customers',
        'payments'  => 'Payment methods',
    ];

    /** Reports that ignore the date range. */
    public const UNDATED = ['stock'];

    public function run(string $report, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return match ($report) {
            'sales'     => $this->sales($from, $to),
            'products'  => $this->topProducts($from, $to),
            'stock'     => $this->lowStock(),
            'customers' => $this->customers($from, $to),
            'payments'  => $this->paymentMethods($from, $to),
        };
    }

    /**
     * Daily sales. Cancelled orders are excluded; refunds are subtracted on the day they were processed.
     */
    public function sales(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = $this->orders($from, $to)
            ->selectRaw('DATE(orders.created_at) as day, COUNT(*) as orders, SUM(orders.total_price) as gross, SUM(orders.discount_amount) as discount, SUM(orders.delivery_charge) as delivery')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $items = $this->orders($from, $to)
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('DATE(orders.created_at) as day, SUM(order_items.quantity) as items')
            ->groupBy('day')
            ->pluck('items', 'day');

        $refunds = DB::table('refunds')
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$from, $to])
            ->selectRaw('DATE(processed_at) as day, SUM(amount) as amount')
            ->groupBy('day')
            ->pluck('amount', 'day');

        $rows = [];
        foreach (CarbonPeriod::create($from->startOfDay(), $to->startOfDay()) as $date) {
            $day = $date->toDateString();
            $o = $orders->get($day);
            $gross = (float) ($o->gross ?? 0);
            $refund = (float) ($refunds[$day] ?? 0);

            $rows[] = [
                'date'     => $day,
                'orders'   => (int) ($o->orders ?? 0),
                'items'    => (int) ($items[$day] ?? 0),
                'discount' => round((float) ($o->discount ?? 0), 2),
                'delivery' => round((float) ($o->delivery ?? 0), 2),
                'gross'    => round($gross, 2),
                'refunds'  => round($refund, 2),
                'net'      => round($gross - $refund, 2),
            ];
        }

        $totals = collect($rows);
        $orderCount = $totals->sum('orders');

        return [
            'columns' => [
                'date' => 'Date', 'orders' => 'Orders', 'items' => 'Items sold', 'discount' => 'Discounts',
                'delivery' => 'Delivery', 'gross' => 'Gross sales', 'refunds' => 'Refunds', 'net' => 'Net sales',
            ],
            'money'   => ['discount', 'delivery', 'gross', 'refunds', 'net'],
            'rows'    => $rows,
            'summary' => [
                'Orders'          => number_format($orderCount),
                'Gross sales'     => $this->money($totals->sum('gross')),
                'Refunds'         => $this->money($totals->sum('refunds')),
                'Net sales'       => $this->money($totals->sum('net')),
                'Avg order value' => $this->money($orderCount ? $totals->sum('gross') / $orderCount : 0),
            ],
            'chart'   => [
                'labels' => $totals->pluck('date')->all(),
                'values' => $totals->pluck('net')->all(),
                'label'  => 'Net sales',
            ],
        ];
    }

    public function topProducts(CarbonImmutable $from, CarbonImmutable $to, int $limit = 50): array
    {
        $stats = $this->orders($from, $to)
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->whereNotNull('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as quantity, SUM(order_items.subtotal) as revenue, COUNT(DISTINCT orders.id) as orders')
            ->groupBy('order_items.product_id')
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get();

        $products = Product::withTrashed()->with('brand')->whereIn('id', $stats->pluck('product_id'))->get()->keyBy('id');

        $rows = $stats->values()->map(function ($row, $i) use ($products) {
            $product = $products->get($row->product_id);

            return [
                'rank'     => $i + 1,
                'product'  => $product?->name ?? 'Product #' . $row->product_id,
                'code'     => $product?->product_id,
                'brand'    => (string) ($product?->brand ?? ''),
                'orders'   => (int) $row->orders,
                'quantity' => (int) $row->quantity,
                'revenue'  => round((float) $row->revenue, 2),
            ];
        })->all();

        return [
            'columns' => ['rank' => '#', 'product' => 'Product', 'code' => 'Code', 'brand' => 'Brand', 'orders' => 'Orders', 'quantity' => 'Units sold', 'revenue' => 'Revenue'],
            'money'   => ['revenue'],
            'rows'    => $rows,
            'summary' => [
                'Products sold' => number_format(count($rows)),
                'Units sold'    => number_format(collect($rows)->sum('quantity')),
                'Revenue'       => $this->money(collect($rows)->sum('revenue')),
            ],
            'chart'   => null,
        ];
    }

    /**
     * Active variants at or below their product's threshold, including those that are out of stock.
     */
    public function lowStock(): array
    {
        $variants = DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->whereNull('products.deleted_at')
            ->where('product_variants.is_active', true)
            ->whereColumn('product_variants.stock', '<=', 'products.low_stock_threshold')
            ->orderBy('product_variants.stock')
            ->orderBy('products.id')
            ->select('product_variants.id', 'product_variants.product_id', 'product_variants.sku', 'product_variants.stock', 'products.low_stock_threshold', 'products.product_id as code')
            ->get();

        $names = Product::withTrashed()->whereIn('id', $variants->pluck('product_id')->unique())->get()->mapWithKeys(fn ($p) => [$p->id => $p->name]);

        $rows = $variants->map(fn ($v) => [
            'product'   => $names[$v->product_id] ?? 'Product #' . $v->product_id,
            'code'      => $v->code,
            'sku'       => $v->sku,
            'stock'     => (int) $v->stock,
            'threshold' => (int) $v->low_stock_threshold,
            'status'    => $v->stock <= 0 ? 'Out of stock' : 'Low stock',
        ])->all();

        $out = collect($rows)->where('stock', '<=', 0)->count();

        return [
            'columns' => ['product' => 'Product', 'code' => 'Code', 'sku' => 'SKU', 'stock' => 'Stock', 'threshold' => 'Threshold', 'status' => 'Status'],
            'money'   => [],
            'rows'    => $rows,
            'summary' => [
                'Variants to restock' => number_format(count($rows)),
                'Out of stock'        => number_format($out),
                'Low stock'           => number_format(count($rows) - $out),
            ],
            'chart'   => null,
        ];
    }

    public function customers(CarbonImmutable $from, CarbonImmutable $to, int $limit = 200): array
    {
        $stats = $this->orders($from, $to)
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->selectRaw('users.id, users.name, users.email, users.phone, users.created_at as joined_at, COUNT(orders.id) as orders, SUM(orders.total_price) as spent, MAX(orders.created_at) as last_order')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.phone', 'users.created_at')
            ->orderByDesc('spent')
            ->limit($limit)
            ->get();

        $rows = $stats->map(fn ($c) => [
            'name'       => $c->name,
            'email'      => $c->email,
            'phone'      => $c->phone,
            'orders'     => (int) $c->orders,
            'spent'      => round((float) $c->spent, 2),
            'avg'        => round($c->orders ? $c->spent / $c->orders : 0, 2),
            'last_order' => substr((string) $c->last_order, 0, 10),
            'joined'     => substr((string) $c->joined_at, 0, 10),
        ])->all();

        $newCustomers = DB::table('users')->whereBetween('created_at', [$from, $to])->count();
        $returning = collect($rows)->where('orders', '>', 1)->count();

        return [
            'columns' => ['name' => 'Customer', 'email' => 'Email', 'phone' => 'Phone', 'orders' => 'Orders', 'spent' => 'Total spent', 'avg' => 'Avg order', 'last_order' => 'Last order', 'joined' => 'Joined'],
            'money'   => ['spent', 'avg'],
            'rows'    => $rows,
            'summary' => [
                'Ordering customers' => number_format(count($rows)),
                'Repeat customers'   => number_format($returning),
                'New sign-ups'       => number_format($newCustomers),
                'Revenue'            => $this->money(collect($rows)->sum('spent')),
            ],
            'chart'   => null,
        ];
    }

    public function paymentMethods(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $collected = collect(PaymentStatus::cases())->filter->isCollected()->map->value->values()->all();
        $placeholders = implode(',', array_fill(0, count($collected), '?'));

        $stats = $this->orders($from, $to)
            ->selectRaw(
                "orders.payment_method as method, COUNT(*) as orders, SUM(orders.total_price) as amount,
                 SUM(CASE WHEN orders.payment_status IN ($placeholders) THEN 1 ELSE 0 END) as paid_orders,
                 SUM(CASE WHEN orders.payment_status IN ($placeholders) THEN orders.total_price ELSE 0 END) as paid_amount",
                [...$collected, ...$collected]
            )
            ->groupBy('orders.payment_method')
            ->orderByDesc('amount')
            ->get();

        $totalAmount = (float) $stats->sum('amount');
        $labels = ['cod' => 'Cash on delivery', 'online' => 'Online (SSLCommerz)'];

        $rows = $stats->map(fn ($m) => [
            'method'      => $labels[$m->method] ?? ucfirst((string) $m->method),
            'orders'      => (int) $m->orders,
            'amount'      => round((float) $m->amount, 2),
            'share'       => $totalAmount > 0 ? round($m->amount / $totalAmount * 100, 1) . '%' : '0%',
            'paid_orders' => (int) $m->paid_orders,
            'paid_amount' => round((float) $m->paid_amount, 2),
            'outstanding' => round((float) $m->amount - (float) $m->paid_amount, 2),
        ])->all();

        return [
            'columns' => ['method' => 'Method', 'orders' => 'Orders', 'amount' => 'Order value', 'share' => 'Share', 'paid_orders' => 'Paid orders', 'paid_amount' => 'Collected', 'outstanding' => 'Outstanding'],
            'money'   => ['amount', 'paid_amount', 'outstanding'],
            'rows'    => $rows,
            'summary' => [
                'Order value' => $this->money($totalAmount),
                'Collected'   => $this->money(collect($rows)->sum('paid_amount')),
                'Outstanding' => $this->money(collect($rows)->sum('outstanding')),
            ],
            'chart'   => null,
        ];
    }

    /**
     * Non-deleted, non-cancelled orders placed within the range.
     */
    private function orders(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return DB::table('orders')
            ->whereNull('orders.deleted_at')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('orders.created_at', [$from, $to]);
    }

    private function money(float $amount): string
    {
        return 'Tk ' . number_format($amount, 2);
    }
}
