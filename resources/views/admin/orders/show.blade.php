@extends('admin.layouts')
@section('title', 'Order #' . $order->order_id)
@section('ui-v2', '1')

@use('App\Enums\OrderStatus')
@use('App\Models\OrderCourier')

@section('content')
@php
    $status = $order->statusEnum();
    $paymentStatus = $order->paymentStatusEnum();
    $subtotal = $order->itemsSubtotal();
    $parcel = $order->orderCourier;
    $dt = 'text-xs font-medium text-gray-500 dark:text-gray-400';
    $dd = 'mt-1 text-sm text-gray-900 dark:text-white';
    $dotTones = [
        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-300',
        'confirmed' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
        'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
        'shipped' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
        'delivered' => 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300',
        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        'returned' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300',
        'refunded' => 'bg-gray-100 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300',
    ];
@endphp

<x-admin.page-header :title="'Order #' . $order->order_id" :description="'Placed ' . $order->created_at->format('d M Y, h:i A')"
                     :breadcrumbs="['Orders' => route('orders.index'), '#' . $order->order_id => null]">
    <x-admin.button variant="secondary" :href="route('admin.orders.invoice.generate', $order->id)" icon="bi-file-earmark-text">Invoice</x-admin.button>
    @if($order->invoice_path)
        <x-admin.button variant="secondary" :href="route('admin.orders.invoice.download', $order->id)" icon="bi-download">Download PDF</x-admin.button>
    @endif
    @can('orders.edit')
        <x-admin.button :href="route('orders.edit', $order)" icon="bi-pencil">Edit order</x-admin.button>
    @endcan
</x-admin.page-header>

<div class="mb-6 flex flex-wrap items-center gap-2">
    <x-admin.status-badge type="order" :status="$order->status" />
    <x-admin.status-badge type="payment" :status="$order->payment_status" />
    <span class="text-sm text-gray-500 dark:text-gray-400">· {{ strtoupper($order->payment_method ?? 'COD') }}</span>
    @if($parcel)
        <span class="text-sm text-gray-500 dark:text-gray-400">· <i class="bi bi-truck" aria-hidden="true"></i> {{ $parcel->courierLabel() }}</span>
    @endif
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="min-w-0 space-y-6 xl:col-span-2">
        {{-- Customer & delivery --}}
        <x-admin.card title="Customer & delivery">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <dl class="space-y-4">
                    <div>
                        <dt class="{{ $dt }}">Name</dt>
                        <dd class="{{ $dd }} font-medium">
                            {{ $order->customerDisplayName() }}
                            @if($order->user)
                                <a href="{{ route('users.show', $order->user) }}" class="ml-1 rounded text-xs text-brand-600 hover:underline focus-ring dark:text-brand-400">View profile</a>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="{{ $dt }}">Email</dt>
                        <dd class="{{ $dd }} break-all">{{ $order->customerDisplayEmail() ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="{{ $dt }}">Phone</dt>
                            <dd class="{{ $dd }} tabular-nums">{{ $order->customerDisplayPhone() ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="{{ $dt }}">Alternative phone</dt>
                            <dd class="{{ $dd }} tabular-nums">{{ $order->alt_phone ?: '—' }}</dd>
                        </div>
                    </div>
                </dl>
                <dl class="space-y-4">
                    <div>
                        <dt class="{{ $dt }}">Shipping address</dt>
                        <dd class="{{ $dd }} whitespace-pre-line">{{ $order->shipping_address ?: '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="{{ $dt }}">City</dt>
                            <dd class="{{ $dd }}">{{ $order->city ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="{{ $dt }}">Region</dt>
                            <dd class="{{ $dd }}">{{ $order->region ?: '—' }}</dd>
                        </div>
                    </div>
                    @if($order->order_notes)
                        <x-admin.alert tone="info" icon="bi-sticky">{{ $order->order_notes }}</x-admin.alert>
                    @endif
                </dl>
            </div>
        </x-admin.card>

        {{-- Items --}}
        <x-admin.card title="Items" :description="$order->orderItems->sum('quantity') . ' ' . \Illuminate\Support\Str::plural('unit', $order->orderItems->sum('quantity'))" :padding="false">
            <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach($order->orderItems as $item)
                    @php($image = $item->product?->images?->first())
                    <li class="flex gap-4 px-6 py-4">
                        <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                            @if($image)
                                <img src="{{ asset('storage/' . $image->image) }}" alt="" class="size-full object-cover" loading="lazy">
                            @else
                                <i class="bi bi-box-seam text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ $item->product?->name ?? 'Deleted product' }}
                                @if($item->product?->trashed())<x-admin.status-badge status="archived" class="ml-1" />@endif
                            </p>
                            @if($item->variant)
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="font-mono">{{ $item->variant->sku ?: '#' . $item->variant->id }}</span>
                                    @if($item->variant->attributeValues->isNotEmpty())
                                        · {{ $item->variant->attributeValues->map(fn ($v) => $v->attribute?->name . ': ' . $v->value)->implode(' · ') }}
                                    @endif
                                </p>
                            @endif
                            <p class="mt-1 text-sm text-gray-500 tabular-nums dark:text-gray-400">{{ $item->quantity }} × Tk {{ number_format($item->price, 2) }}</p>
                        </div>
                        <p class="shrink-0 font-medium text-gray-900 tabular-nums dark:text-white">Tk {{ number_format($item->subtotal, 2) }}</p>
                    </li>
                @endforeach
            </ul>
            <dl class="space-y-2 border-t border-gray-200 bg-gray-50 px-6 py-4 text-sm dark:border-gray-800 dark:bg-gray-900/60">
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Items subtotal</dt><dd class="tabular-nums">Tk {{ number_format($subtotal, 2) }}</dd></div>
                @if((float) $order->discount_amount > 0)
                    <div class="flex justify-between text-green-700 dark:text-green-400"><dt>Coupon {{ $order->coupon_code }}</dt><dd class="tabular-nums">− Tk {{ number_format($order->discount_amount, 2) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Delivery charge</dt><dd class="tabular-nums">Tk {{ number_format($order->delivery_charge ?? 0, 2) }}</dd></div>
                <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold text-gray-900 dark:border-gray-800 dark:text-white"><dt>Total</dt><dd class="tabular-nums">Tk {{ number_format($order->total_price, 2) }}</dd></div>
            </dl>
        </x-admin.card>

        {{-- Payment --}}
        <x-admin.card title="Payments" :description="'Method: ' . strtoupper($order->payment_method ?? 'COD')" :padding="false">
            <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($order->payments as $payment)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm text-gray-900 dark:text-white">{{ $payment->transaction_id }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ strtoupper($payment->payment_method) }} · {{ optional($payment->payment_date ?? $payment->created_at)->format('d M Y, h:i A') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-medium text-gray-900 tabular-nums dark:text-white">{{ $payment->currency ?? 'BDT' }} {{ number_format($payment->amount, 2) }}</span>
                            <x-admin.status-badge type="payment" :status="$payment->status" />
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-5 text-sm text-gray-500 dark:text-gray-400">
                        No payment recorded yet{{ $order->payment_method === 'cod' ? '. Cash is recorded when the order is delivered.' : '.' }}
                    </li>
                @endforelse
            </ul>
        </x-admin.card>

        {{-- Courier --}}
        <x-admin.card title="Courier">
            @if($parcel)
                @can('orders.edit')
                    <x-slot:actions>
                        <form method="POST" action="{{ route('orders.courier.refresh', $order) }}">
                            @csrf
                            <x-admin.button type="submit" variant="secondary" size="sm" icon="bi-arrow-repeat">Refresh status</x-admin.button>
                        </form>
                    </x-slot:actions>
                @endcan
                <dl class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div><dt class="{{ $dt }}">Courier</dt><dd class="{{ $dd }} font-medium">{{ $parcel->courierLabel() }}</dd></div>
                    <div><dt class="{{ $dt }}">Consignment</dt><dd class="{{ $dd }} font-mono">{{ $parcel->consignment_id }}</dd></div>
                    <div>
                        <dt class="{{ $dt }}">Tracking code</dt>
                        <dd class="{{ $dd }} font-mono">
                            @if($parcel->trackingUrl())
                                <a href="{{ $parcel->trackingUrl() }}" target="_blank" rel="noopener" class="rounded text-brand-600 hover:underline focus-ring dark:text-brand-400">{{ $parcel->tracking_code ?: $parcel->consignment_id }} <i class="bi bi-box-arrow-up-right text-xs" aria-hidden="true"></i></a>
                            @else
                                {{ $parcel->tracking_code ?: '—' }}
                            @endif
                        </dd>
                    </div>
                    <div><dt class="{{ $dt }}">Courier status</dt><dd class="{{ $dd }}"><x-admin.status-badge :status="$parcel->status ?? 'unknown'" tone="indigo" /></dd></div>
                </dl>
                <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">Booked {{ $parcel->created_at->format('d M Y, h:i A') }}{{ $parcel->admin ? ' by ' . $parcel->admin->name : '' }}</p>
                @if($order->courierLogs->isNotEmpty())
                    <ul class="mt-4 divide-y divide-gray-200 border-t border-gray-200 text-sm dark:divide-gray-800 dark:border-gray-800">
                        @foreach($order->courierLogs->sortByDesc('created_at') as $log)
                            <li class="flex flex-wrap justify-between gap-2 py-2">
                                <span><span class="font-medium text-gray-900 dark:text-white">{{ \Illuminate\Support\Str::of($log->status_to)->replace('_', ' ')->ucfirst() }}</span> <span class="text-gray-500 dark:text-gray-400">{{ $log->remarks }}</span></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->format('d M, h:i A') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @elseif($status === OrderStatus::Processing)
                @can('orders.edit')
                    <form method="POST" action="{{ route('orders.courier.store', $order) }}" class="grid grid-cols-1 gap-4 md:grid-cols-2"
                          x-data="pathaoLocations()" x-init="$watch('courier', value => value === 'pathao' && loadCities())">
                        @csrf
                        <x-admin.select name="courier" label="Courier" required x-model="courier" :options="OrderCourier::COURIERS" :value="old('courier', 'steadfast')" />
                        <x-admin.input name="cod_amount" type="number" label="Cash to collect" prefix="Tk" step="0.01" min="0" required
                                       :value="$paymentStatus?->isCollected() ? 0 : $order->total_price" />
                        <template x-if="courier === 'pathao'">
                            <div class="grid grid-cols-1 gap-4 md:col-span-2 md:grid-cols-3">
                                <x-admin.field label="Pathao city" for="pathao-city">
                                    <select id="pathao-city" name="city_id" x-model="cityId" @change="loadZones()"
                                            class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900">
                                        <option value="">Auto-detect from address</option>
                                        <template x-for="city in cities" :key="city.city_id"><option :value="city.city_id" x-text="city.city_name"></option></template>
                                    </select>
                                </x-admin.field>
                                <x-admin.field label="Pathao zone" for="pathao-zone">
                                    <select id="pathao-zone" name="zone_id" :disabled="!zones.length"
                                            class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900">
                                        <option value="">Auto-detect from address</option>
                                        <template x-for="zone in zones" :key="zone.zone_id"><option :value="zone.zone_id" x-text="zone.zone_name"></option></template>
                                    </select>
                                </x-admin.field>
                                <x-admin.input name="item_weight" type="number" label="Weight" suffix="kg" step="0.1" min="0.1" value="0.5" />
                                <p class="text-xs font-medium text-yellow-700 md:col-span-3 dark:text-yellow-400" x-show="error" x-text="error"></p>
                            </div>
                        </template>
                        <div class="md:col-span-2">
                            <x-admin.input name="note" label="Note for rider" optional maxlength="250" :value="$order->order_notes" />
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-4 md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Booking sends the parcel to the courier and marks the order <strong>Shipped</strong>.</p>
                            <x-admin.button type="submit" icon="bi-truck">Book parcel</x-admin.button>
                        </div>
                    </form>
                @endcan
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Move the order to <strong>Processing</strong> to book a courier.</p>
            @endif
        </x-admin.card>

        {{-- Refunds --}}
        <x-admin.card title="Refunds" :description="'Refundable: Tk ' . number_format($refundable, 2)">
            @if($order->refunds->isNotEmpty())
                <ul class="-mt-2 mb-2 divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($order->refunds as $refund)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2 font-medium text-gray-900 tabular-nums dark:text-white">
                                    Tk {{ number_format($refund->amount, 2) }}
                                    <x-admin.status-badge :status="$refund->status" />
                                    @if($refund->restock)
                                        <x-admin.status-badge tone="indigo" :dot="false" :label="$refund->restocked_at ? 'Restocked' : 'Restock on completion'" />
                                    @endif
                                </p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $refund->reason }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    #{{ $refund->id }} · {{ $refundMethods[$refund->method] ?? 'No method' }}{{ $refund->reference ? ' · ' . $refund->reference : '' }} · {{ $refund->created_at->format('d M Y') }}{{ $refund->admin ? ' · ' . $refund->admin->name : '' }}
                                </p>
                            </div>
                            @if($refund->isPending())
                                @can('payments.edit')
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('refunds.complete', $refund) }}">
                                            @csrf
                                            <x-admin.button type="submit" size="sm" icon="bi-check-lg">Complete</x-admin.button>
                                        </form>
                                        <form method="POST" action="{{ route('refunds.reject', $refund) }}"
                                              data-confirm="The refund is marked rejected and no money is returned." data-confirm-title="Reject this refund?" data-confirm-button="Reject">
                                            @csrf
                                            <x-admin.button type="submit" variant="secondary" size="sm" :loading="false">Reject</x-admin.button>
                                        </form>
                                    </div>
                                @endcan
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @can('payments.edit')
                @if($refundable > 0)
                    <form method="POST" action="{{ route('orders.refunds.store', $order) }}" @class(['grid grid-cols-1 gap-4 md:grid-cols-3', 'border-t border-gray-200 pt-4 dark:border-gray-800' => $order->refunds->isNotEmpty()])
                          data-confirm="The refund is recorded against this order." data-confirm-title="Record this refund?" data-confirm-button="Record refund">
                        @csrf
                        <x-admin.input name="amount" type="number" label="Amount" prefix="Tk" step="0.01" min="0.01" :max="$refundable" :value="$refundable" required />
                        <x-admin.select name="method" label="Method" optional placeholder="—" :options="$refundMethods" />
                        <x-admin.input name="reference" label="Reference" optional maxlength="255" placeholder="Transaction / cheque no." />
                        <div class="md:col-span-3">
                            <x-admin.textarea name="reason" label="Reason" rows="2" maxlength="1000" required />
                        </div>
                        <div class="space-y-3 md:col-span-2">
                            <div @class(['opacity-50' => $order->stock_released_at])>
                                <x-admin.checkbox name="restock" label="Restock returned items" :disabled="$order->stock_released_at || $status?->holdsStock()"
                                                  :description="$order->stock_released_at ? 'Already restocked.' : null" />
                            </div>
                            <x-admin.checkbox name="complete" label="Money already returned" description="Marks the refund completed right away." :checked="true" />
                        </div>
                        <div class="flex items-end justify-end">
                            <x-admin.button type="submit" variant="danger" icon="bi-arrow-counterclockwise">Record refund</x-admin.button>
                        </div>
                    </form>
                @elseif($order->refunds->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Refunds become available once payment has been collected.</p>
                @endif
            @endcan
        </x-admin.card>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        @can('orders.edit')
            <x-admin.card title="Update status" description="Only valid next steps are shown.">
                <div class="space-y-2">
                    @forelse($order->allowedTransitions() as $next)
                        <form method="POST" action="{{ route('orders.updateStatus', $order) }}" x-data="{ open: false }"
                              class="rounded-lg border border-gray-200 dark:border-gray-800">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $next->value }}">
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-gray-800 hover:bg-gray-50 focus-ring dark:text-gray-200 dark:hover:bg-gray-800/60">
                                <span class="flex size-7 items-center justify-center rounded-md {{ $dotTones[$next->value] ?? $dotTones['refunded'] }}" aria-hidden="true"><i class="bi {{ $next->icon() }} text-xs"></i></span>
                                Mark as {{ $next->label() }}
                                <i class="bi bi-chevron-down ml-auto text-xs text-gray-400 transition-transform" :class="open && 'rotate-180'" aria-hidden="true"></i>
                            </button>
                            <div x-show="open" x-cloak class="space-y-3 border-t border-gray-200 p-3 dark:border-gray-800">
                                <x-admin.input name="note" :id="'note-' . $next->value" label="Note for the timeline" optional maxlength="255" :old="false" />
                                @if($next === OrderStatus::Returned && !$order->stock_released_at)
                                    <x-admin.checkbox name="restock" :id="'restock-' . $next->value" label="Put the returned items back in stock" :checked="true" />
                                @endif
                                @if($next === OrderStatus::Cancelled && $status?->holdsStock())
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Reserved stock will be released.</p>
                                @endif
                                @if($next->customerMessage() && $order->customerDisplayEmail())
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><i class="bi bi-envelope" aria-hidden="true"></i> The customer will be emailed.</p>
                                @endif
                                <x-admin.button type="submit" size="sm" class="w-full">Confirm {{ strtolower($next->label()) }}</x-admin.button>
                            </div>
                        </form>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No further status changes are possible.</p>
                    @endforelse
                </div>
            </x-admin.card>
        @endcan

        <x-admin.card title="Timeline">
            <ol class="relative ml-3 space-y-6 border-l border-gray-200 dark:border-gray-800">
                @forelse($order->statusHistories->reverse() as $history)
                    @php($to = $history->toStatus())
                    <li class="ml-6">
                        <span class="absolute -left-3.5 flex size-7 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-900 {{ $dotTones[$history->to_status] ?? $dotTones['refunded'] }}" aria-hidden="true">
                            <i class="bi {{ $to?->icon() ?? 'bi-dot' }} text-xs"></i>
                        </span>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $to?->label() ?? $history->to_status }}
                            @if($history->fromStatus())
                                <span class="font-normal text-gray-500 dark:text-gray-400">from {{ $history->fromStatus()->label() }}</span>
                            @endif
                        </p>
                        @if($history->note)
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $history->note }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <time datetime="{{ $history->created_at->toIso8601String() }}">{{ $history->created_at->format('d M Y, h:i A') }}</time> · {{ $history->admin?->name ?? 'System / customer' }}
                        </p>
                    </li>
                @empty
                    <li class="ml-6 text-sm text-gray-500 dark:text-gray-400">No history recorded.</li>
                @endforelse
            </ol>
        </x-admin.card>
    </div>
</div>

@push('scripts')
<script>
    function pathaoLocations() {
        return {
            courier: @js(old('courier', 'steadfast')),
            cities: [], zones: [], cityId: '', error: '',
            loadCities() {
                if (this.cities.length) return;
                fetch(@js(route('orders.courier.pathao.cities')), { headers: { Accept: 'application/json' } })
                    .then(r => r.json().then(body => ({ ok: r.ok, body })))
                    .then(({ ok, body }) => ok ? this.cities = body : this.error = body.message);
            },
            loadZones() {
                this.zones = [];
                if (!this.cityId) return;
                fetch(@js(url('admin/orders/courier/pathao/cities')) + '/' + this.cityId + '/zones', { headers: { Accept: 'application/json' } })
                    .then(r => r.json().then(body => ({ ok: r.ok, body })))
                    .then(({ ok, body }) => ok ? this.zones = body : this.error = body.message);
            },
        };
    }
</script>
@endpush
@endsection
