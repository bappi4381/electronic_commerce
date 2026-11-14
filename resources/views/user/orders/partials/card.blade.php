{{-- Order summary card. Expects $order with orderItems.product.images loaded. --}}
@php
    $status = $order->statusEnum()?->customerFacing();
    $items = $order->orderItems;
    $units = (int) $items->sum('quantity');
    $canCancel = $status?->holdsStock() && !$order->paymentStatusEnum()?->isCollected();
    $fmt = fn ($amount) => money($amount, null, fmod((float) $amount, 1.0) != 0.0 ? 2 : 0);
@endphp

<article class="rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white transition-shadow hover:shadow-[var(--sf-shadow-sm)]">
    <header class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--sf-border)] px-4 py-3 sm:px-5">
        <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1 text-sm">
            <a href="{{ route('user.orders.details', $order) }}" class="rounded font-mono font-semibold text-slate-900 hover:text-brand-700 sf-focus">#{{ $order->order_id }}</a>
            <span class="text-slate-500">{{ $order->created_at->format('d M Y') }}</span>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ \App\Enums\OrderStatus::badge($order->status) }}">
            <i class="bi {{ $status?->icon() ?? 'bi-circle' }}" aria-hidden="true"></i> {{ __($status?->label() ?? ucfirst($order->status)) }}
        </span>
    </header>
    <div class="flex flex-wrap items-center gap-4 px-4 py-4 sm:px-5">
        <ul class="flex -space-x-3" aria-label="{{ __('Items') }}">
            @foreach($items->take(4) as $item)
                <li class="relative rounded-[var(--sf-radius-sm)] ring-2 ring-white" title="{{ $item->product?->name }}">
                    <x-store.image :src="\App\Support\Storefront::imageUrl($item->product?->images->first()?->image)" :alt="(string) ($item->product?->name ?? '')" :width="56" :height="56"
                                   class="size-14 rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] bg-slate-50 [&_i]:!text-xl" img-class="p-1 mix-blend-multiply" />
                </li>
            @endforeach
            @if($items->count() > 4)
                <li class="relative flex size-14 items-center justify-center rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] bg-slate-100 text-sm font-semibold text-slate-600 ring-2 ring-white">+{{ $items->count() - 4 }}</li>
            @endif
        </ul>
        <div class="min-w-0 flex-1">
            <p class="line-clamp-1 text-sm font-medium text-slate-900">{{ $items->first()?->product?->name ?? __('Order items') }}@if($items->count() > 1) <span class="font-normal text-slate-500">{{ __('and :count more', ['count' => $items->count() - 1]) }}</span>@endif</p>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ trans_choice(':count item|:count items', $units, ['count' => localized_number($units)]) }} ·
                <span class="font-semibold tabular-nums text-slate-900">{{ $fmt($order->total_price) }}</span> ·
                {{ $order->payment_method === 'cod' ? __('Cash on delivery') : __('Online') }}
            </p>
        </div>
        <div class="flex w-full items-center gap-2 sm:w-auto">
            @if($canCancel)
                <form method="POST" action="{{ route('user.orders.cancel', $order) }}" class="flex-1 sm:flex-none"
                      x-data @submit="if (!confirm(@js(__('Cancel order #:id? This cannot be undone.', ['id' => $order->order_id])))) $event.preventDefault()">
                    @csrf @method('PATCH')
                    <button type="submit" class="flex h-10 w-full items-center justify-center rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] px-4 text-sm font-semibold text-slate-600 hover:border-rose-300 hover:text-rose-700 sf-focus">{{ __('Cancel') }}</button>
                </form>
            @endif
            <a href="{{ route('user.orders.details', $order) }}" class="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-[var(--sf-radius-sm)] bg-navy-900 px-4 text-sm font-semibold text-white hover:bg-navy-800 sm:flex-none sf-focus">
                {{ __('View order') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</article>
