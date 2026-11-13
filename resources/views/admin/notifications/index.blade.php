@extends('admin.layouts')
@section('title', 'Notifications')
@section('ui-v2', '1')

@section('content')
@php
    $unreadOnly = request('filter') === 'unread';
    $tones = [
        'emerald' => 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400',
        'amber'   => 'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/10 dark:text-yellow-400',
        'red'     => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400',
        'indigo'  => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
        'slate'   => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
    ];
@endphp

<x-admin.page-header title="Notifications" description="New orders, stock alerts, chat messages and reviews awaiting approval."
                     :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Notifications' => null]">
    <form method="POST" action="{{ route('admin.notifications.readAll') }}">
        @csrf
        <x-admin.button type="submit" variant="secondary" icon="bi-check2-all">Mark all as read</x-admin.button>
    </form>
</x-admin.page-header>

<x-admin.data-table :paginator="$notifications" :columns="[
        ['label' => 'Notification', 'sortable' => true],
        ['label' => 'Status'],
        ['label' => 'Received', 'sortable' => true, 'align' => 'right'],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]" empty-title="No notifications" empty-description="You'll see new orders, low stock and messages here." empty-icon="bi-bell">
    <x-slot:toolbar>
        <div class="inline-flex rounded-lg border border-gray-300 p-0.5 dark:border-gray-700" role="group" aria-label="Filter notifications">
            @foreach(['All' => null, 'Unread' => 'unread'] as $label => $filter)
                @php($current = ($filter === 'unread') === $unreadOnly)
                <a href="{{ route('admin.notifications.index', array_filter(['filter' => $filter])) }}" @if($current) aria-current="page" @endif
                   @class(['rounded-md px-3 py-1.5 text-sm font-medium focus-ring', 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $current, 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => !$current])>{{ $label }}</a>
            @endforeach
        </div>
    </x-slot:toolbar>

    @foreach($notifications as $notification)
        @php($data = $notification->data)
        <tr @class(['bg-brand-50/40 dark:bg-brand-500/5' => !$notification->read_at])>
            <td data-primary data-sort="{{ $data['title'] ?? '' }}">
                <a href="{{ route('admin.notifications.open', $notification->id) }}" class="flex items-center gap-3 rounded-lg focus-ring">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tones[$data['color'] ?? 'slate'] ?? $tones['slate'] }}" aria-hidden="true">
                        <i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate font-medium text-gray-900 dark:text-white">{{ $data['title'] ?? 'Notification' }}</span>
                        <span class="block max-w-xl truncate text-xs text-gray-500 dark:text-gray-400">{{ $data['body'] ?? '' }}</span>
                    </span>
                </a>
            </td>
            <td data-label="Status">
                <x-admin.status-badge :status="$notification->read_at ? 'read' : 'unread'" :tone="$notification->read_at ? 'gray' : 'brand'" />
            </td>
            <td data-label="Received" data-sort="{{ $notification->created_at->timestamp }}" class="whitespace-nowrap text-right text-gray-500 dark:text-gray-400">
                <time datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->format('d M Y, H:i') }}">{{ $notification->created_at->diffForHumans() }}</time>
            </td>
            <td class="text-right">
                <form method="POST" action="{{ route('admin.notifications.destroy', $notification->id) }}" class="inline"
                      data-confirm="This notification will be removed from your list." data-confirm-title="Remove notification?" data-confirm-button="Remove">
                    @csrf @method('DELETE')
                    <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" aria-label="Remove notification">
                        <span class="md:sr-only">Remove</span>
                    </x-admin.button>
                </form>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
