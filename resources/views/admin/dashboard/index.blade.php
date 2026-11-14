@extends('admin.layouts')
@section('title', 'Dashboard')
@section('ui-v2', '1')

@section('content')
@php
    $admin = Auth::guard('admin')->user();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $trendLabel = "vs previous {$periodDays} days";

    $attention = collect([
        ['label' => 'Risky orders on hold', 'count' => $pending['on_hold'], 'icon' => 'bi-shield-exclamation', 'url' => route('orders.index', ['status' => 'on_hold']), 'can' => 'orders.view', 'tone' => 'red'],
        ['label' => 'Risk checks that failed', 'count' => $pending['risk_unknown'], 'icon' => 'bi-question-octagon', 'url' => route('orders.index', ['risk' => 'unknown']), 'can' => 'orders.view'],
        ['label' => 'Orders awaiting confirmation', 'count' => $pending['orders'], 'icon' => 'bi-hourglass-split', 'url' => route('orders.index', ['status' => 'pending']), 'can' => 'orders.view'],
        ['label' => 'Reviews to moderate', 'count' => $pending['reviews'], 'icon' => 'bi-star-half', 'url' => route('admin.reviews.index'), 'can' => 'reviews.view'],
        ['label' => 'Unread customer messages', 'count' => $pending['messages'], 'icon' => 'bi-chat-dots', 'url' => route('admin.messages.index'), 'can' => 'messages.view'],
        ['label' => 'Refunds to process', 'count' => $pending['refunds'], 'icon' => 'bi-arrow-counterclockwise', 'url' => route('payments.index'), 'can' => 'payments.view'],
    ])->filter(fn ($item) => $admin?->can($item['can']));
@endphp

<x-admin.page-header :title="$greeting . ', ' . \Illuminate\Support\Str::before($admin->name, ' ')"
                     description="Here's how the store performed over the last {{ $periodDays }} days. Cancelled orders are excluded.">
    @can('reports.view')
        <x-admin.button variant="secondary" :href="route('admin.reports.index')" icon="bi-bar-chart-line">View reports</x-admin.button>
    @endcan
    @can('products.create')
        <x-admin.button :href="route('admin.products.create')" icon="bi-plus-lg">Add product</x-admin.button>
    @endcan
</x-admin.page-header>

{{-- KPIs --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-admin.stat-card label="Net revenue" :value="'Tk ' . number_format($stats['revenue']['value'], 2)" icon="bi-cash-stack"
                       :trend="$stats['revenue']['growth']" :trend-label="$stats['revenue']['growth'] === null ? 'No sales in the previous period' : $trendLabel" />
    <x-admin.stat-card label="Orders" :value="number_format($stats['orders']['value'])" icon="bi-bag-check"
                       :trend="$stats['orders']['growth']" :trend-label="$stats['orders']['growth'] === null ? 'No orders in the previous period' : $trendLabel"
                       :href="$admin->can('orders.view') ? route('orders.index') : null" />
    <x-admin.stat-card label="Active customers" :value="number_format($stats['customers']['value'])" icon="bi-people"
                       :trend="$stats['customers']['growth']" :trend-label="$stats['customers']['growth'] === null ? 'Customers who placed an order' : $trendLabel"
                       :href="$admin->can('users.view') ? route('users.index') : null" />
    <x-admin.stat-card label="Products" :value="number_format($totalProducts)" icon="bi-box-seam"
                       :hint="$outOfStockCount ? $outOfStockCount . ' variant(s) out of stock' : 'All variants in stock'"
                       :href="$admin->can('products.view') ? route('admin.products.index') : null" />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    {{-- Sales chart --}}
    <x-admin.card class="xl:col-span-2" :padding="false" x-data="salesChart({{ \Illuminate\Support\Js::from($chart) }})">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sales overview</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-medium text-gray-900 tabular-nums dark:text-white" x-text="summary"></span>
                    <span x-text="'over the last ' + range + ' months'"></span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <div class="inline-flex rounded-lg border border-gray-300 p-0.5 dark:border-gray-700" role="group" aria-label="Chart metric">
                    <template x-for="option in [{ id: 'revenue', label: 'Revenue' }, { id: 'orders', label: 'Orders' }]" :key="option.id">
                        <button type="button" @click="metric = option.id" :aria-pressed="(metric === option.id).toString()" x-text="option.label"
                                class="rounded-md px-3 py-1 text-sm font-medium focus-ring"
                                :class="metric === option.id ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"></button>
                    </template>
                </div>
                <div class="inline-flex rounded-lg border border-gray-300 p-0.5 dark:border-gray-700" role="group" aria-label="Chart range">
                    <template x-for="option in [3, 6]" :key="option">
                        <button type="button" @click="range = option" :aria-pressed="(range === option).toString()" x-text="option + 'M'"
                                class="rounded-md px-3 py-1 text-sm font-medium focus-ring"
                                :class="range === option ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"></button>
                    </template>
                </div>
            </div>
        </div>
        <div class="relative h-80 p-6">
            <div x-show="!ready" class="absolute inset-6"><x-admin.skeleton type="text" :lines="6" class="h-full" /></div>
            <canvas x-ref="canvas" role="img" :aria-label="'Monthly ' + metric + ' chart'"></canvas>
        </div>
    </x-admin.card>

    <div class="flex flex-col gap-6">
    {{-- Needs attention --}}
    <x-admin.card title="Needs attention" description="Work waiting on your team." :padding="false">
        <ul class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($attention as $item)
                <li>
                    <a href="{{ $item['url'] }}" class="flex items-center gap-3 px-6 py-3.5 transition-colors hover:bg-gray-50 focus-ring dark:hover:bg-gray-800/50">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400" aria-hidden="true">
                            <i class="bi {{ $item['icon'] }}"></i>
                        </span>
                        <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                        <span @class([
                            'min-w-8 rounded-md px-2 py-0.5 text-center text-sm font-semibold tabular-nums',
                            'bg-red-600 text-white dark:bg-red-500' => $item['count'] > 0 && ($item['tone'] ?? null) === 'red',
                            'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $item['count'] > 0 && ($item['tone'] ?? null) !== 'red',
                            'text-gray-400 dark:text-gray-500' => $item['count'] === 0,
                        ])>{{ $item['count'] }}</span>
                    </a>
                </li>
            @empty
                <li><x-admin.empty-state title="Nothing to review" icon="bi-check2-circle" /></li>
            @endforelse
        </ul>
    </x-admin.card>

    {{-- Courier balance --}}
    @if($courier)
        <x-admin.card title="Courier balance" description="Steadfast merchant account.">
            <x-slot:actions>
                @if($courier['configured'])
                    <x-admin.button variant="ghost" size="sm" icon="bi-arrow-repeat" :href="route('admin.dashboard', ['refresh_balance' => 1])" title="Check the balance again">Refresh</x-admin.button>
                @endif
            </x-slot:actions>
            @if(!$courier['configured'])
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Steadfast is not connected.
                    @can('settings.view')<a href="{{ route('admin.settings.index') }}#courier" class="rounded font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">Add the API keys in Settings › Courier</a>.@endcan
                </p>
            @elseif($courier['error'])
                <x-admin.alert tone="warning">{{ $courier['error'] }}</x-admin.alert>
            @else
                <p class="text-2xl font-semibold text-gray-900 tabular-nums dark:text-white">Tk {{ number_format($courier['amount'], 2) }}</p>
            @endif
            @if($courier['checked_at'])
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Checked {{ \Illuminate\Support\Carbon::parse($courier['checked_at'])->diffForHumans() }}</p>
            @endif
            <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-200 pt-4 text-sm dark:border-gray-800">
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">Parcels in transit</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900 tabular-nums dark:text-white">{{ number_format($courier['in_transit']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">Failed to send</dt>
                    <dd @class(['mt-0.5 font-semibold tabular-nums', 'text-red-600 dark:text-red-400' => $courier['failed'] > 0, 'text-gray-900 dark:text-white' => $courier['failed'] === 0])>{{ number_format($courier['failed']) }}</dd>
                </div>
            </dl>
        </x-admin.card>
    @endif
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    {{-- Recent orders --}}
    <div class="xl:col-span-2">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recent orders</h2>
            @can('orders.view')
                <x-admin.button variant="link" :href="route('orders.index')" icon-right="bi-arrow-right">View all</x-admin.button>
            @endcan
        </div>
        <x-admin.data-table :columns="[
                ['label' => 'Order', 'sortable' => true],
                ['label' => 'Customer', 'sortable' => true],
                ['label' => 'Status'],
                ['label' => 'Payment'],
                ['label' => 'Total', 'sortable' => true, 'align' => 'right'],
            ]" empty-title="No orders yet" empty-description="New orders will appear here as soon as customers check out." empty-icon="bi-bag">
            @foreach($recentOrders as $order)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td data-primary data-sort="{{ $order->created_at?->timestamp }}">
                        <a href="{{ route('orders.show', $order) }}" class="rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">#{{ $order->order_id }}</a>
                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">{{ $order->created_at?->format('d M Y, H:i') }}</span>
                    </td>
                    <td data-label="Customer">{{ $order->customerDisplayName() }}</td>
                    <td data-label="Status"><x-admin.status-badge type="order" :status="$order->status" /></td>
                    <td data-label="Payment"><x-admin.status-badge type="payment" :status="$order->payment_status" /></td>
                    <td data-label="Total" class="whitespace-nowrap text-right font-medium text-gray-900 tabular-nums dark:text-white">Tk {{ number_format($order->total_price, 2) }}</td>
                </tr>
            @endforeach
        </x-admin.data-table>
    </div>

    {{-- Low stock --}}
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Low stock
                @if($lowStockCount)<span class="ml-1 text-sm font-normal text-gray-500 dark:text-gray-400">{{ $lowStockCount }} variant(s)</span>@endif
            </h2>
            @if($lowStockCount > $lowStockVariants->count() && $admin->can('reports.view'))
                <x-admin.button variant="link" :href="route('admin.reports.index', ['report' => 'stock'])" icon-right="bi-arrow-right">See all</x-admin.button>
            @endif
        </div>
        <x-admin.card :padding="false">
            <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($lowStockVariants as $variant)
                    <li class="flex items-center gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $variant->product?->name ?? 'Product #' . $variant->product_id }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $variant->sku ?: 'Default variant' }} · minimum {{ $variant->low_stock_threshold }}</p>
                        </div>
                        @if($variant->stock <= 0)
                            <x-admin.status-badge status="out of stock" />
                        @else
                            <x-admin.status-badge status="low stock" :label="$variant->stock . ' left'" />
                        @endif
                        @can('products.edit')
                            <x-admin.button variant="ghost" size="sm" :href="route('admin.products.edit', $variant->product_id)" icon="bi-pencil" aria-label="Edit {{ $variant->product?->name }}" />
                        @endcan
                    </li>
                @empty
                    <li><x-admin.empty-state title="Stock levels look healthy" description="No active variant is at or below its threshold." icon="bi-check2-circle" /></li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    function salesChart(data) {
        let chart = null;
        const money = (value) => 'Tk ' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        return {
            metric: 'revenue',
            range: 6,
            ready: false,
            get series() {
                const from = Math.max(0, data.labels.length - this.range);
                return { labels: data.labels.slice(from), values: data[this.metric].slice(from) };
            },
            get summary() {
                const total = this.series.values.reduce((sum, value) => sum + Number(value), 0);
                return this.metric === 'revenue' ? money(total) : total.toLocaleString() + ' orders';
            },
            init() {
                this.$watch('metric', () => this.render());
                this.$watch('range', () => this.render());
                this.$watch('$store.theme.dark', () => this.render());
                this.$nextTick(() => this.render());
            },
            render() {
                if (!window.Chart) return;
                const dark = document.documentElement.classList.contains('dark');
                const styles = getComputedStyle(document.documentElement);
                const color = (name) => styles.getPropertyValue(name).trim();
                const brand = color(dark ? '--color-brand-400' : '--color-brand-600');
                const grid = color(dark ? '--color-gray-800' : '--color-gray-200');
                const text = color(dark ? '--color-gray-400' : '--color-gray-500');
                const isMoney = this.metric === 'revenue';

                chart?.destroy();
                chart = new Chart(this.$refs.canvas, {
                    type: 'bar',
                    data: {
                        labels: this.series.labels,
                        datasets: [{
                            label: isMoney ? 'Net revenue' : 'Orders',
                            data: this.series.values,
                            backgroundColor: brand,
                            hoverBackgroundColor: brand,
                            borderRadius: 6,
                            maxBarThickness: 48,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 250 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: { label: (item) => isMoney ? money(item.parsed.y) : item.parsed.y + ' orders' },
                            },
                        },
                        scales: {
                            x: { grid: { display: false }, border: { display: false }, ticks: { color: text, font: { family: 'Inter' } } },
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: { color: grid },
                                ticks: { color: text, font: { family: 'Inter' }, precision: 0, callback: (value) => isMoney ? 'Tk ' + Number(value).toLocaleString() : value },
                            },
                        },
                    },
                });
                this.ready = true;
            },
        };
    }
</script>
@endpush
