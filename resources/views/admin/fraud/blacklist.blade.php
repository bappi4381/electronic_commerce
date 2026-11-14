@extends('admin.layouts')
@section('title', 'Phone blacklist')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="Phone blacklist" description="Checkout refuses orders from these numbers. Customers only see a request to contact you." />

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="min-w-0 xl:col-span-2">
        <x-admin.data-table :paginator="$entries" search="q" search-placeholder="Search a number…"
            :columns="[
                ['label' => 'Number'],
                ['label' => 'Reason'],
                ['label' => 'Added'],
                ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
            ]"
            empty-title="No blacklisted numbers" empty-description="Add a number here or from an order's Delivery risk card." empty-icon="bi-telephone-x">
            @foreach($entries as $entry)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td data-primary>
                        <span class="font-mono font-medium text-gray-900 dark:text-white">{{ $entry->phone }}</span>
                        @if($orderCounts[$entry->phone] ?? 0)
                            <a href="{{ route('orders.index', ['q' => $entry->phone]) }}" class="block rounded text-xs text-brand-600 hover:underline focus-ring dark:text-brand-400">
                                {{ $orderCounts[$entry->phone] }} {{ \Illuminate\Support\Str::plural('order', $orderCounts[$entry->phone]) }}
                            </a>
                        @endif
                    </td>
                    <td data-label="Reason" class="max-w-xs"><span class="line-clamp-2 text-gray-700 dark:text-gray-300">{{ $entry->reason }}</span></td>
                    <td data-label="Added" class="whitespace-nowrap">
                        <span class="block text-gray-700 dark:text-gray-300">{{ $entry->created_at->format('d M Y') }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $entry->admin?->name ?? '—' }}</span>
                    </td>
                    <td class="text-right">
                        @can('orders.edit')
                            <form method="POST" action="{{ route('admin.blacklist.destroy', $entry) }}"
                                  data-confirm="Orders from this number will be accepted again." data-confirm-title="Remove {{ $entry->phone }}?" data-confirm-button="Remove">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" aria-label="Remove {{ $entry->phone }}">Remove</x-admin.button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>
    </div>

    @can('orders.edit')
        <x-admin.card title="Add a number" class="self-start">
            <form method="POST" action="{{ route('admin.blacklist.store') }}" class="space-y-4">
                @csrf
                <x-admin.input name="phone" label="Mobile number" required inputmode="tel" maxlength="20" placeholder="01XXXXXXXXX" />
                <x-admin.textarea name="reason" label="Reason" required rows="3" maxlength="255" placeholder="e.g. Refused delivery 3 times" />
                <x-admin.button type="submit" icon="bi-telephone-x" class="w-full">Blacklist</x-admin.button>
            </form>
        </x-admin.card>
    @endcan
</div>
@endsection
