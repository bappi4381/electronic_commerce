@extends('admin.layouts')
@section('title', 'Marketing')
@section('ui-v2', '1')

@section('content')
@php
    $flashTitle = \App\Models\Setting::get('flash_deal_title', 'Flash Deals');
    $flashEndAt = app(\App\Services\FlashSale::class)->endsAt();
    $liveCoupons = $coupons->filter(fn ($c) => $c->status
        && !($c->ends_at && $c->ends_at->isPast())
        && !($c->starts_at && $c->starts_at->isFuture())
        && !($c->usage_limit !== null && $c->used_count >= $c->usage_limit))->count();
    $canEdit = auth()->user()->can('marketing.edit');
@endphp

<x-admin.page-header title="Marketing" description="Coupons, product discounts and the flash deal campaign.">
    @can('marketing.view')
        <x-admin.button variant="secondary" :href="route('admin.subscribers.index')" icon="bi-envelope-paper">Subscribers</x-admin.button>
    @endcan
    @can('marketing.create')
        <x-admin.button :href="route('coupons.create')" icon="bi-plus-lg">New coupon</x-admin.button>
    @endcan
</x-admin.page-header>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-admin.stat-card label="Live coupons" :value="$liveCoupons . ' / ' . $coupons->count()" icon="bi-ticket-perforated" />
    <x-admin.stat-card label="Products on sale" :value="number_format($discountedProductsCount)" icon="bi-percent" />
    <x-admin.stat-card label="Flash deal ends" :value="$flashEndAt ? $flashEndAt->format('d M, H:i') : 'Not scheduled'"
                       :hint="$flashEndAt ? ($flashEndAt->isPast() ? 'Ended ' . $flashEndAt->diffForHumans() : 'In ' . $flashEndAt->diffForHumans(null, true)) : null" icon="bi-lightning-charge" />
</div>

<x-admin.card title="Flash deal campaign" description="Tick “Flash” on a product below and give it a flash discount. It applies only between the start and end time; regular prices come back afterwards." class="mb-6">
    <form method="POST" action="{{ route('marketing.flashDeal') }}">
        @csrf
        @include('admin.marketing.partials.flash-campaign', ['canEdit' => $canEdit, 'titleRequired' => true])
        @if($canEdit)
            <div class="mt-5 flex justify-end">
                <x-admin.button type="submit" icon="bi-calendar-check">Save schedule</x-admin.button>
            </div>
        @endif
    </form>
</x-admin.card>
@if($canEdit)
    <form id="flash-end-form" method="POST" action="{{ route('marketing.flashDeal.end') }}" class="hidden"
          data-confirm="Flash prices switch back to regular prices and every product leaves the campaign." data-confirm-title="End the flash deal now?" data-confirm-button="End campaign">@csrf</form>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    {{-- Coupons --}}
    <x-admin.card title="Coupons" :description="$coupons->count() . ' ' . \Illuminate\Support\Str::plural('code', $coupons->count())" :padding="false" class="min-w-0 self-start">
        <ul class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($coupons as $coupon)
                @php
                    $expired = $coupon->ends_at && $coupon->ends_at->isPast();
                    $scheduled = $coupon->starts_at && $coupon->starts_at->isFuture();
                    $exhausted = $coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit;
                    $live = $coupon->status && !$expired && !$scheduled && !$exhausted;
                    [$stateLabel, $stateTone] = match (true) {
                        $live => ['Live', 'green'],
                        $expired => ['Expired', 'red'],
                        $scheduled => ['Scheduled', 'blue'],
                        $exhausted => ['Used up', 'orange'],
                        default => ['Disabled', 'gray'],
                    };
                @endphp
                <li class="flex flex-wrap items-center gap-4 px-6 py-4">
                    <span @class(['flex size-11 shrink-0 items-center justify-center rounded-lg border-2 border-dashed text-base font-semibold',
                                  'border-brand-300 text-brand-600 dark:border-brand-500/50 dark:text-brand-400' => $live,
                                  'border-gray-200 text-gray-400 dark:border-gray-700' => !$live]) aria-hidden="true">
                        {{ $coupon->type === 'percent' ? '%' : '৳' }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-sm font-semibold uppercase text-gray-900 dark:text-white">{{ $coupon->code }}</span>
                            <x-admin.status-badge :tone="$stateTone" :label="$stateLabel" />
                        </p>
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{{ $coupon->summary() }}{{ $coupon->min_order_amount ? ' · min Tk ' . number_format($coupon->min_order_amount, 0) : '' }}</p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            Used {{ $coupon->used_count }}{{ $coupon->usage_limit ? ' of ' . $coupon->usage_limit : '' }}{{ $coupon->per_user_limit ? ' · ' . $coupon->per_user_limit . ' per customer' : '' }}{{ $coupon->ends_at ? ' · ends ' . $coupon->ends_at->format('d M Y, H:i') : '' }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @can('marketing.edit')
                            <form method="POST" action="{{ route('coupons.toggle', $coupon) }}">
                                @csrf @method('PATCH')
                                <button type="submit" role="switch" aria-checked="{{ $coupon->status ? 'true' : 'false' }}" title="{{ $coupon->status ? 'Disable' : 'Enable' }}"
                                        @class(['relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus-ring',
                                                'bg-brand-600 dark:bg-brand-500' => $coupon->status, 'bg-gray-200 dark:bg-gray-700' => !$coupon->status])>
                                    <span class="sr-only">{{ $coupon->status ? 'Disable' : 'Enable' }} {{ $coupon->code }}</span>
                                    <span aria-hidden="true" @class(['pointer-events-none inline-block size-5 rounded-full bg-white shadow-sm transition-transform', 'translate-x-5' => $coupon->status])></span>
                                </button>
                            </form>
                            <x-admin.button variant="ghost" size="sm" :href="route('coupons.edit', $coupon)" icon="bi-pencil" title="Edit" aria-label="Edit {{ $coupon->code }}" />
                        @endcan
                        @can('marketing.delete')
                            <form method="POST" action="{{ route('coupons.destroy', $coupon) }}"
                                  data-confirm="Customers will no longer be able to use this code." data-confirm-title="Delete coupon {{ $coupon->code }}?" data-confirm-button="Delete">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $coupon->code }}" />
                            </form>
                        @endcan
                    </div>
                </li>
            @empty
                <li>
                    <x-admin.empty-state title="No coupons yet" description="Create a code customers can enter at checkout." icon="bi-ticket-perforated">
                        @can('marketing.create')
                            <x-admin.button :href="route('coupons.create')" icon="bi-plus-lg" size="sm">New coupon</x-admin.button>
                        @endcan
                    </x-admin.empty-state>
                </li>
            @endforelse
        </ul>
    </x-admin.card>

    {{-- Product discounts --}}
    <div class="min-w-0">
        <x-admin.data-table :paginator="$products" search="search" search-placeholder="Search products…"
            :columns="[
                ['label' => 'Product', 'sortable' => true],
                ['label' => 'Regular % · Flash %', 'align' => 'right'],
            ]"
            empty-title="No products" empty-icon="bi-box-seam">
            @foreach($products as $product)
                @php
                    $image = $product->images->first();
                    $productName = $product->getTranslation('name', 'en');
                @endphp
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td data-primary data-sort="{{ $productName }}" class="md:w-full md:max-w-0">
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                                @if($image)
                                    <img src="{{ asset('storage/' . $image->image) }}" alt="" class="size-full object-cover" loading="lazy">
                                @else
                                    <i class="bi bi-image text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                                @endif
                            </span>
                            <div class="min-w-0">
                                <span class="block truncate font-medium text-gray-900 dark:text-white">{{ $productName }}</span>
                                <span class="block truncate text-xs font-normal text-gray-500 tabular-nums dark:text-gray-400">
                                    Tk {{ number_format($product->price, 0) }}
                                    @if($product->discount > 0)
                                        → <span class="font-medium text-green-700 dark:text-green-400">Tk {{ number_format($product->discounted_price, 0) }}</span>
                                    @endif
                                    · {{ $product->category?->getTranslation('name', 'en') ?? 'General' }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td data-label="Discount" class="whitespace-nowrap text-right">
                        @if($canEdit)
                            <form method="POST" action="{{ route('marketing.updateDiscount', $product) }}" class="inline-flex items-center gap-3"
                                  x-data="{ flash: @js((bool) $product->is_flash_deal) }">
                                @csrf
                                <div class="relative w-20">
                                    <label for="discount-{{ $product->id }}" class="sr-only">Regular discount for {{ $productName }}</label>
                                    <input id="discount-{{ $product->id }}" type="number" name="discount" min="0" max="100" step="0.01" value="{{ $product->regularDiscount() }}" title="Regular discount"
                                           class="block h-9 w-full rounded-lg border border-gray-300 bg-white pl-3 pr-7 text-right text-sm tabular-nums shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900">
                                    <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-xs text-gray-400">%</span>
                                </div>
                                <label class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" name="is_flash_deal" value="1" x-model="flash"
                                           class="size-4 rounded border-gray-300 text-brand-600 focus:ring-2 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-800">
                                    Flash
                                </label>
                                <div class="relative w-20" x-show="flash" x-cloak>
                                    <label for="flash-discount-{{ $product->id }}" class="sr-only">Flash discount for {{ $productName }}</label>
                                    <input id="flash-discount-{{ $product->id }}" type="number" name="flash_discount" min="0" max="100" step="0.01" value="{{ $product->flash_discount !== null ? (float) $product->flash_discount : ($product->regularDiscount() ?: '') }}"
                                           placeholder="Flash" title="Flash discount (applies only while the campaign runs)" :required="flash"
                                           class="block h-9 w-full rounded-lg border border-indigo-300 bg-indigo-50/50 pl-3 pr-7 text-right text-sm tabular-nums shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-indigo-500/40 dark:bg-gray-900">
                                    <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-xs text-indigo-400">%</span>
                                </div>
                                <x-admin.button type="submit" variant="secondary" size="sm" icon="bi-check-lg" aria-label="Save discount for {{ $productName }}" />
                            </form>
                        @else
                            <span class="tabular-nums">{{ $product->regularDiscount() }}%</span>
                            @if($product->is_flash_deal)<x-admin.status-badge tone="indigo" :dot="false" :label="'Flash ' . (float) ($product->flash_discount ?? $product->discount) . '%'" class="ml-1" />@endif
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>
    </div>
</div>
@endsection
