@extends('admin.layouts')
@section('title', 'Reports')
@section('ui-v2', '1')

@section('content')
@php
    $exportQuery = $undated ? ['report' => $report] : $filters;
    $control = 'h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200';
    $presets = [7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '12 months'];
    $activePreset = !$undated && $filters['to_date'] === now()->toDateString()
        ? collect($presets)->keys()->first(fn ($days) => $filters['from_date'] === now()->subDays($days - 1)->toDateString())
        : null;
    $description = $undated
        ? 'Current stock levels · ' . now()->format('d M Y, h:i A')
        : \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') . ' – ' . \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') . ' · cancelled orders excluded';
@endphp

<x-admin.page-header title="Reports" :description="$description">
    @foreach(['xlsx' => ['Excel', 'bi-file-earmark-excel'], 'csv' => ['CSV', 'bi-filetype-csv']] as $type => [$name, $icon])
        <x-admin.button variant="secondary" :href="route('admin.reports.export', array_merge(['type' => $type], $exportQuery))" :icon="$icon">Export {{ $name }}</x-admin.button>
    @endforeach
</x-admin.page-header>

<nav aria-label="Reports" class="scrollbar-thin mb-6 flex gap-1 overflow-x-auto border-b border-gray-200 dark:border-gray-800">
    @foreach($reports as $key => $name)
        @php($active = $report === $key)
        <a href="{{ route('admin.reports.index', array_merge($filters, ['report' => $key])) }}" @if($active) aria-current="page" @endif
           @class(['-mb-px shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors focus-ring',
                   'border-brand-600 text-brand-700 dark:border-brand-400 dark:text-brand-300' => $active,
                   'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => !$active])>{{ $name }}</a>
    @endforeach
</nav>

@unless($undated)
    <form method="GET" action="{{ route('admin.reports.index') }}" class="mb-6 flex flex-wrap items-center gap-2">
        <input type="hidden" name="report" value="{{ $report }}">
        <div class="inline-flex rounded-lg border border-gray-300 bg-white p-0.5 shadow-xs dark:border-gray-700 dark:bg-gray-900" role="group" aria-label="Date range presets">
            @foreach($presets as $days => $name)
                <a href="{{ route('admin.reports.index', ['report' => $report, 'from_date' => now()->subDays($days - 1)->toDateString(), 'to_date' => now()->toDateString()]) }}"
                   @if($activePreset === $days) aria-current="true" @endif
                   @class(['rounded-md px-3 py-1.5 text-sm font-medium focus-ring',
                           'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $activePreset === $days,
                           'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' => $activePreset !== $days])>{{ $name }}</a>
            @endforeach
        </div>
        <label for="report-from" class="sr-only">From</label>
        <input id="report-from" type="date" name="from_date" value="{{ $filters['from_date'] }}" class="{{ $control }}">
        <span class="text-sm text-gray-400" aria-hidden="true">–</span>
        <label for="report-to" class="sr-only">To</label>
        <input id="report-to" type="date" name="to_date" value="{{ $filters['to_date'] }}" class="{{ $control }}">
        <x-admin.button type="submit" variant="secondary" :loading="false">Apply</x-admin.button>
    </form>
@endunless

@if(count($result['summary']))
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
        @foreach($result['summary'] as $name => $value)
            <x-admin.stat-card :label="$name" :value="$value" class="!p-5" />
        @endforeach
    </div>
@endif

@if($result['chart'])
    <x-admin.card :title="$result['chart']['label'] ?? 'Trend'" class="mb-6">
        <div class="h-64"><canvas id="reportChart" aria-label="{{ $result['chart']['label'] ?? 'Report chart' }}" role="img"></canvas></div>
    </x-admin.card>
@endif

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="admin-table min-w-full text-sm [&_td]:px-4 [&_td]:py-3 [&_td]:text-gray-700 dark:[&_td]:text-gray-300">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/40">
                <tr>
                    @foreach($result['columns'] as $key => $name)
                        <th scope="col" @class(['whitespace-nowrap px-4 py-3 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400', 'text-right' => in_array($key, $result['money'], true), 'text-left' => !in_array($key, $result['money'], true)])>{{ $name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($result['rows'] as $row)
                    <tr @class(['hover:bg-gray-50 dark:hover:bg-gray-800/40', 'bg-red-50/50 dark:bg-red-500/5' => ($row['status'] ?? null) === 'Out of stock'])>
                        @foreach($result['columns'] as $key => $name)
                            @if($loop->first)
                                <td data-primary class="font-medium text-gray-900 dark:text-white">{{ $row[$key] ?? '—' }}</td>
                            @elseif(in_array($key, $result['money'], true))
                                <td data-label="{{ $name }}" class="whitespace-nowrap text-right font-medium tabular-nums">{{ number_format($row[$key], 2) }}</td>
                            @elseif($key === 'status')
                                <td data-label="{{ $name }}"><x-admin.status-badge :status="strtolower($row[$key])" :label="$row[$key]" /></td>
                            @else
                                <td data-label="{{ $name }}">{{ $row[$key] ?? '—' }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($result['columns']) }}" class="admin-table-empty">
                            <x-admin.empty-state title="No data for this period" description="Try a wider date range." icon="bi-bar-chart" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@if($result['chart'])
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (() => {
        const chart = @js($result['chart']);
        const css = getComputedStyle(document.documentElement);
        const dark = document.documentElement.classList.contains('dark');
        const brand = css.getPropertyValue('--color-brand-500').trim() || 'rgb(79, 70, 229)';
        const grid = dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        const text = dark ? '#9ca3af' : '#6b7280';
        const money = (v) => 'Tk ' + Number(v).toLocaleString();

        new Chart(document.getElementById('reportChart'), {
            type: chart.labels.length > 45 ? 'line' : 'bar',
            data: {
                labels: chart.labels,
                datasets: [{ label: chart.label, data: chart.values, backgroundColor: brand, borderColor: brand, borderRadius: 6, fill: false, tension: 0.3, pointRadius: 0 }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => money(ctx.parsed.y) } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { color: text, callback: money } },
                    x: { grid: { display: false }, ticks: { color: text, maxRotation: 0, autoSkip: true } },
                },
            },
        });
    })();
</script>
@endpush
@endif
