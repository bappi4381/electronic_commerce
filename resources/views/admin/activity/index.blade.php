@extends('admin.layouts')
@section('title', 'Activity log')
@section('ui-v2', '1')

@section('content')
@php
    $control = 'h-10 rounded-lg border border-gray-300 bg-white pl-3 text-sm text-gray-700 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200';
    $format = function ($value) {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }
        return $value === null || $value === '' ? '—' : (string) $value;
    };
    $eventTones = ['created' => 'green', 'updated' => 'blue', 'deleted' => 'red', 'restored' => 'indigo'];
@endphp

<x-admin.page-header title="Activity log" description="Who changed what across products, orders, stock, settings and access control." />

<x-admin.data-table :paginator="$activities"
    :columns="[
        ['label' => 'Change'],
        ['label' => 'Who'],
        ['label' => 'Details'],
        ['label' => 'When', 'align' => 'right'],
    ]"
    empty-title="No activity recorded" empty-icon="bi-clock-history">
    <x-slot:filters>
        <label for="filter-log" class="sr-only">Area</label>
        <select id="filter-log" name="log" class="{{ $control }} pr-9">
            <option value="">All areas</option>
            @foreach($logs as $value => $name)
                <option value="{{ $value }}" @selected(($filters['log'] ?? null) === $value)>{{ $name }}</option>
            @endforeach
        </select>
        <label for="filter-event" class="sr-only">Event</label>
        <select id="filter-event" name="event" class="{{ $control }} pr-9">
            <option value="">All events</option>
            @foreach($events as $event)
                <option value="{{ $event }}" @selected(($filters['event'] ?? null) === $event)>{{ ucfirst(str_replace('_', ' ', $event)) }}</option>
            @endforeach
        </select>
        <label for="filter-admin" class="sr-only">Admin</label>
        <select id="filter-admin" name="admin_id" class="{{ $control }} pr-9">
            <option value="">Anyone</option>
            @foreach($admins as $admin)
                <option value="{{ $admin->id }}" @selected(($filters['admin_id'] ?? null) == $admin->id)>{{ $admin->name }}</option>
            @endforeach
        </select>
        <label for="filter-from" class="sr-only">From date</label>
        <input id="filter-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="{{ $control }} pr-3" title="From">
        <label for="filter-to" class="sr-only">To date</label>
        <input id="filter-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="{{ $control }} pr-3" title="To">
    </x-slot:filters>

    @foreach($activities as $activity)
        @php
            $new = $activity->properties['attributes'] ?? [];
            $old = $activity->properties['old'] ?? [];
            $extra = collect($activity->properties)->except(['attributes', 'old'])->all();
        @endphp
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40 md:[&>td]:align-top">
            <td data-primary class="md:w-1/4">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white">{{ $activity->description }}</p>
                    <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs font-normal text-gray-500 dark:text-gray-400">
                        <span>{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>
                        @if($activity->event)<x-admin.status-badge :tone="$eventTones[$activity->event] ?? 'gray'" :status="$activity->event" :dot="false" />@endif
                        @if($activity->subject instanceof \App\Models\Order)
                            <a href="{{ route('orders.show', $activity->subject) }}" class="rounded text-brand-600 hover:underline focus-ring dark:text-brand-400">#{{ $activity->subject->order_id }}</a>
                        @endif
                    </p>
                </div>
            </td>
            <td data-label="Who" class="whitespace-nowrap">
                <div class="text-right md:text-left">
                    <span class="block text-gray-900 dark:text-white">{{ $activity->causer?->name ?? 'System / customer' }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $logs[$activity->log_name] ?? $activity->log_name }}</span>
                </div>
            </td>
            <td data-label="Details" class="md:max-w-xl">
                @if($new || $extra)
                    <dl class="space-y-1 text-xs">
                        @foreach($new as $field => $value)
                            <div class="break-words">
                                <dt class="inline font-medium text-gray-500 dark:text-gray-400">{{ $field }}:</dt>
                                <dd class="inline">
                                    @if(array_key_exists($field, $old))
                                        <span class="text-red-600 line-through dark:text-red-400">{{ \Illuminate\Support\Str::limit($format($old[$field]), 80) }}</span>
                                        <i class="bi bi-arrow-right text-gray-400" aria-label="changed to"></i>
                                    @endif
                                    <span class="font-medium text-green-700 dark:text-green-400">{{ \Illuminate\Support\Str::limit($format($value), 80) }}</span>
                                </dd>
                            </div>
                        @endforeach
                        @foreach($extra as $field => $value)
                            <div class="break-words">
                                <dt class="inline font-medium text-gray-500 dark:text-gray-400">{{ $field }}:</dt>
                                <dd class="inline text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($format($value), 160) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <span class="text-gray-400">—</span>
                @endif
            </td>
            <td data-label="When" class="whitespace-nowrap text-right text-gray-500 dark:text-gray-400">
                <time datetime="{{ $activity->created_at->toIso8601String() }}" title="{{ $activity->created_at->format('d M Y, h:i:s A') }}">
                    <span class="block">{{ $activity->created_at->format('d M Y') }}</span>
                    <span class="block text-xs">{{ $activity->created_at->format('h:i A') }}</span>
                </time>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
