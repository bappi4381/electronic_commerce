<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\ProductVariant;
use App\Models\Refund;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const PERIOD_DAYS = 30;
    private const CHART_MONTHS = 6;

    public function index()
    {
        $now = CarbonImmutable::now();
        $periodStart = $now->subDays(self::PERIOD_DAYS - 1)->startOfDay();
        $previousStart = $periodStart->subDays(self::PERIOD_DAYS);
        $previousEnd = $periodStart->subSecond();

        $current = $this->periodStats($periodStart, $now);
        $previous = $this->periodStats($previousStart, $previousEnd);

        $stats = [
            'revenue'   => ['value' => $current['revenue'], 'growth' => $this->growth($current['revenue'], $previous['revenue'])],
            'orders'    => ['value' => $current['orders'], 'growth' => $this->growth($current['orders'], $previous['orders'])],
            'customers' => ['value' => $current['customers'], 'growth' => $this->growth($current['customers'], $previous['customers'])],
        ];

        $totalProducts = Product::count();
        $outOfStockCount = $this->lowStockQuery()->where('product_variants.stock', '<=', 0)->count();
        $lowStockCount = $this->lowStockQuery()->count();
        $lowStockVariants = $this->lowStockQuery()
            ->with('product:id,name,product_id')
            ->orderBy('product_variants.stock')
            ->select('product_variants.*', 'products.low_stock_threshold')
            ->take(8)
            ->get();

        $pending = [
            'orders'   => Order::where('status', OrderStatus::Pending->value)->count(),
            'reviews'  => ProductComment::withoutGlobalScope('active_user')->where('status', ProductComment::STATUS_PENDING)->count(),
            'messages' => Message::where('is_admin', false)->where('is_read', false)->count(),
            'refunds'  => Refund::where('status', 'pending')->count(),
        ];

        $recentOrders = Order::with('user')->latest()->take(5)->get();

        return view('admin.dashboard.index', [
            'periodDays'       => self::PERIOD_DAYS,
            'stats'            => $stats,
            'totalProducts'    => $totalProducts,
            'lowStockCount'    => $lowStockCount,
            'outOfStockCount'  => $outOfStockCount,
            'lowStockVariants' => $lowStockVariants,
            'pending'          => $pending,
            'recentOrders'     => $recentOrders,
            'chart'            => $this->monthlySales($now),
        ]);
    }

    /**
     * Net sales (non-cancelled orders minus completed refunds), order count and distinct ordering customers.
     */
    private function periodStats(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = $this->orders($from, $to)
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_price), 0) as gross, COUNT(DISTINCT user_id) as customers')
            ->first();

        $refunds = (float) DB::table('refunds')->where('status', 'completed')->whereBetween('processed_at', [$from, $to])->sum('amount');

        return [
            'revenue'   => round((float) $orders->gross - $refunds, 2),
            'orders'    => (int) $orders->orders,
            'customers' => (int) $orders->customers,
        ];
    }

    /**
     * Net sales per calendar month for the last six months, oldest first, with empty months as zero.
     */
    private function monthlySales(CarbonImmutable $now): array
    {
        $start = $now->startOfMonth()->subMonths(self::CHART_MONTHS - 1);

        $sales = $this->orders($start, $now)
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, SUM(total_price) as total, COUNT(*) as orders')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->get()
            ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->y, $row->m));

        $refunds = DB::table('refunds')
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$start, $now])
            ->selectRaw('YEAR(processed_at) as y, MONTH(processed_at) as m, SUM(amount) as total')
            ->groupByRaw('YEAR(processed_at), MONTH(processed_at)')
            ->get()
            ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->y, $row->m));

        $chart = ['labels' => [], 'revenue' => [], 'orders' => []];
        for ($month = $start; $month <= $now; $month = $month->addMonth()) {
            $key = $month->format('Y-m');
            $chart['labels'][] = $month->format('M Y');
            $chart['revenue'][] = round((float) ($sales[$key]->total ?? 0) - (float) ($refunds[$key]->total ?? 0), 2);
            $chart['orders'][] = (int) ($sales[$key]->orders ?? 0);
        }

        return $chart;
    }

    private function orders(CarbonImmutable $from, CarbonImmutable $to)
    {
        return DB::table('orders')
            ->whereNull('deleted_at')
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Active variants at or below their product's threshold, out-of-stock included.
     */
    private function lowStockQuery()
    {
        return ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->whereNull('products.deleted_at')
            ->where('product_variants.is_active', true)
            ->whereColumn('product_variants.stock', '<=', 'products.low_stock_threshold');
    }

    /**
     * Percentage change; null when there is nothing to compare against.
     */
    private function growth(float|int $current, float|int $previous): ?float
    {
        if ($previous == 0) {
            return $current == 0 ? 0.0 : null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }
}
