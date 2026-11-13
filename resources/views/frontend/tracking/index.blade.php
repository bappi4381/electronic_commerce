@extends('frontend.layout')

@section('title', 'Track Your Order')

@section('content')

{{-- ══ HERO BANNER ══ --}}
<div class="relative bg-slate-900 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-sky-950"></div>
    {{-- Decorative blobs --}}
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-sky-400/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-4 py-20 lg:py-28 text-center">
        <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-5 py-2 mb-8 backdrop-blur-sm">
            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-white/60">Live Tracking System</span>
        </div>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white tracking-tighter leading-none mb-6">
            Track Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-sky-300">Package</span>
        </h1>
        <p class="text-slate-400 text-sm md:text-base font-medium max-w-lg mx-auto leading-relaxed">
            Enter your Order ID and registered phone number to get real-time delivery updates.
        </p>

        {{-- ══ SEARCH FORM ══ --}}
        <form action="{{ route('tracking.track') }}" method="POST"
              class="mt-12 max-w-2xl mx-auto bg-white/5 border border-white/10 backdrop-blur-md rounded-3xl p-6 shadow-2xl shadow-black/30">
            @csrf
            @if(session('error'))
                <div class="flex items-center gap-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-2xl px-5 py-4 mb-6 text-left">
                    <i class="bi bi-exclamation-circle-fill text-xl flex-shrink-0"></i>
                    <span class="text-[11px] font-bold uppercase tracking-widest">{{ session('error') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="relative group">
                    <i class="bi bi-hash absolute left-5 top-1/2 -translate-y-1/2 text-white/30 group-focus-within:text-primary transition-colors text-xl pointer-events-none"></i>
                    <input type="text" name="order_id" id="order_id"
                           value="{{ old('order_id', isset($order) ? $order->order_id : '') }}"
                           class="w-full bg-white/5 border border-white/10 focus:border-primary/60 focus:bg-white/10 rounded-2xl pl-12 pr-5 py-4 text-sm font-bold text-white placeholder:text-white/30 outline-none transition-all"
                           placeholder="Order ID — ORD-2024…" required>
                    @error('order_id') <p class="text-[10px] text-rose-400 font-bold mt-2 ml-1 text-left">{{ $message }}</p> @enderror
                </div>
                <div class="relative group">
                    <i class="bi bi-phone absolute left-5 top-1/2 -translate-y-1/2 text-white/30 group-focus-within:text-primary transition-colors text-xl pointer-events-none"></i>
                    <input type="text" name="phone" id="phone"
                           class="w-full bg-white/5 border border-white/10 focus:border-primary/60 focus:bg-white/10 rounded-2xl pl-12 pr-5 py-4 text-sm font-bold text-white placeholder:text-white/30 outline-none transition-all"
                           placeholder="Phone — 01XXXXXXXXX" required>
                    @error('phone') <p class="text-[10px] text-rose-400 font-bold mt-2 ml-1 text-left">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit"
                    class="w-full py-4 bg-primary hover:bg-sky-500 active:scale-[0.98] text-white font-black text-[11px] uppercase tracking-[0.25em] rounded-2xl transition-all shadow-xl shadow-primary/30 flex items-center justify-center gap-3 group">
                <i class="bi bi-search text-base"></i>
                Track Package
                <i class="bi bi-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </button>
        </form>

        {{-- Tips --}}
        <div class="flex flex-wrap justify-center gap-6 mt-8">
            @foreach(["Check your order confirmation email for the Order ID", "Use the phone number registered at checkout", "Updates may take 24h after dispatch"] as $tip)
            <div class="flex items-center gap-2 text-[10px] font-bold text-white/40 uppercase tracking-widest">
                <i class="bi bi-check-circle-fill text-primary/60 text-sm"></i>
                {{ $tip }}
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ══ RESULTS SECTION ══ --}}
@isset($order)
<div class="bg-slate-50 py-16">
    <div class="max-w-5xl mx-auto px-4 space-y-8">

        {{-- ══ STATUS SUMMARY CARD ══ --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-8 py-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400 mb-1">Order ID</p>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tighter">{{ $order->order_id }}</h2>
                </div>
                <div class="flex items-center gap-3">
                    @php $statusEnum = $order->statusEnum(); @endphp
                    <span class="inline-flex items-center gap-2 {{ \App\Enums\OrderStatus::badge($order->status) }} border rounded-full px-4 py-2 text-[10px] font-black uppercase tracking-widest">
                        <i class="bi {{ $statusEnum?->icon() ?? 'bi-clock-fill' }}"></i>
                        {{ $statusEnum?->label() ?? ucfirst($order->status) }}
                    </span>
                </div>
            </div>

            {{-- Meta row --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-slate-100 border-t border-slate-100">
                @php
                    $metas = [
                        ['label' => 'Placed On',      'value' => $order->created_at->format('d M Y'),        'icon' => 'bi-calendar3'],
                        ['label' => 'Payment',        'value' => strtoupper($order->payment_method),         'icon' => 'bi-credit-card'],
                        ['label' => 'Courier',        'value' => $order->orderCourier ? ucfirst($order->orderCourier->courier_name) : 'Assigning…', 'icon' => 'bi-truck'],
                        ['label' => 'Tracking Code',  'value' => $order->orderCourier?->tracking_code ?? 'Pending', 'icon' => 'bi-qr-code'],
                    ];
                @endphp
                @foreach($metas as $meta)
                <div class="px-6 py-5 flex flex-col gap-1">
                    <div class="flex items-center gap-2 text-[9px] font-black uppercase tracking-widest text-slate-400">
                        <i class="bi {{ $meta['icon'] }} text-primary"></i>
                        {{ $meta['label'] }}
                    </div>
                    @if($meta['label'] === 'Tracking Code' && $order->orderCourier?->trackingUrl())
                        <a href="{{ $order->orderCourier->trackingUrl() }}" target="_blank" rel="noopener" class="text-sm font-black text-primary tracking-tight hover:underline">
                            {{ $meta['value'] }} <i class="bi bi-box-arrow-up-right text-xs"></i>
                        </a>
                    @else
                        <p class="text-sm font-black text-slate-900 tracking-tight">{{ $meta['value'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- ══ PROGRESS STEPPER ══ --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 px-8 py-10">
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400 mb-10 text-center">Delivery Progress</p>
            @php
                $stepStatuses = \App\Enums\OrderStatus::fulfilmentSteps();
                $steps = collect($stepStatuses)->map(fn ($s) => ['key' => $s->value, 'label' => $s->label(), 'icon' => $s->icon()])->all();
                $currentIdx  = array_search($statusEnum, $stepStatuses, true);
                // Returned/refunded orders were delivered or shipped first; cancelled orders never progressed.
                $isCancelled = in_array($order->status, ['cancelled', 'returned', 'refunded'], true);
                $currentIdx  = $currentIdx === false ? 0 : $currentIdx;
                $progress    = count($steps) > 1 ? round($currentIdx / (count($steps) - 1) * 100) : 0;
            @endphp

            <div class="flex items-start justify-between relative">
                {{-- Connector line --}}
                <div class="absolute top-5 left-[10%] right-[10%] h-0.5 bg-slate-100">
                    @if(!$isCancelled)
                    <div class="h-full bg-primary transition-all duration-1000 rounded-full" style="width: {{ $progress }}%"></div>
                    @endif
                </div>

                @foreach($steps as $i => $step)
                    @php
                        $reached  = !$isCancelled && $i <= $currentIdx;
                        $active   = !$isCancelled && $i === $currentIdx;
                    @endphp
                    <div class="flex flex-col items-center gap-3 z-10 flex-1">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-base transition-all duration-500 border-2 shadow-md
                            @if($isCancelled) bg-white border-slate-100 text-slate-200
                            @elseif($reached && !$active) bg-primary border-primary text-white shadow-primary/30
                            @elseif($active) bg-primary border-primary text-white shadow-primary/30 ring-4 ring-primary/20
                            @else bg-white border-slate-100 text-slate-300 @endif">
                            @if($reached && !$active)
                                <i class="bi bi-check-lg"></i>
                            @else
                                <i class="bi {{ $step['icon'] }}"></i>
                            @endif
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-center leading-tight
                            {{ $reached ? 'text-slate-800' : 'text-slate-300' }}">
                            {{ $step['label'] }}
                        </span>
                    </div>
                @endforeach

                @if($isCancelled)
                <div class="flex flex-col items-center gap-3 z-10 flex-1">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center bg-rose-100 border-2 border-rose-200 text-rose-500 shadow-sm">
                        <i class="bi {{ $statusEnum?->icon() ?? 'bi-x-lg' }}"></i>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-rose-400">{{ $statusEnum?->label() }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- ══ TIMELINE ══ --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 px-8 py-10">
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400 mb-10">Delivery Timeline</p>

            @php
                // Real timeline: our own status changes plus courier scan updates, newest first.
                $timeline = $order->statusHistories->map(fn ($h) => [
                    'at' => $h->created_at,
                    'title' => $h->toStatus()?->label() ?? ucfirst($h->to_status),
                    'desc' => $h->note ?: $h->toStatus()?->customerMessage(),
                    'icon' => $h->toStatus()?->icon() ?? 'bi-dot',
                ])->concat($order->courierLogs->map(fn ($log) => [
                    'at' => $log->created_at,
                    'title' => ($order->orderCourier?->courierLabel() ?? 'Courier') . ': ' . ucfirst(str_replace('_', ' ', $log->status_to)),
                    'desc' => $log->remarks,
                    'icon' => 'bi-truck',
                ]))->sortByDesc('at')->values();
            @endphp

            @if($timeline->isNotEmpty())
                <div class="space-y-0 relative">
                    <div class="absolute left-5 top-0 bottom-0 w-px bg-slate-100"></div>
                    @foreach($timeline as $i => $event)
                    <div class="relative pl-16 pb-8 last:pb-0 flex items-start gap-4">
                        <div class="absolute left-0 w-10 h-10 rounded-xl flex items-center justify-center border-2 z-10 {{ $i === 0 ? 'border-primary bg-primary text-white shadow-sm shadow-primary/20' : 'border-primary bg-white text-primary' }}">
                            <i class="bi {{ $event['icon'] }} text-sm"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-xs font-black uppercase tracking-widest text-slate-900">{{ $event['title'] }}</h4>
                                    @if($event['desc'])
                                        <p class="text-[10px] font-medium text-slate-400 mt-1">{{ $event['desc'] }}</p>
                                    @endif
                                </div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-300 flex-shrink-0 pt-1">
                                    {{ $event['at']->format('d M, h:i A') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 text-center py-10">No updates yet.</p>
            @endif
        </div>

        {{-- ══ HELP FOOTER ══ --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach([
                ['icon' => 'bi-headset',    'title' => 'Need Help?',         'desc' => 'Contact our support team 24/7.',       'link' => route('contact'), 'cta' => 'Contact Us'],
                ['icon' => 'bi-bag-fill',   'title' => 'View Orders',        'desc' => 'See all your past orders.',            'link' => route('user.dashboard'), 'cta' => 'My Orders'],
                ['icon' => 'bi-arrow-repeat','title' => 'Track Again',       'desc' => 'Search for a different order.',        'link' => route('tracking.index'), 'cta' => 'Track Another'],
            ] as $card)
            <a href="{{ $card['link'] }}" class="bg-white border border-slate-100 rounded-2xl p-6 flex flex-col gap-3 hover:border-primary/30 hover:shadow-md transition-all group no-underline">
                <div class="w-10 h-10 rounded-xl bg-primary/5 text-primary flex items-center justify-center text-lg group-hover:bg-primary group-hover:text-white transition-all">
                    <i class="bi {{ $card['icon'] }}"></i>
                </div>
                <div>
                    <h4 class="text-[11px] font-black uppercase tracking-widest text-slate-900 mb-1">{{ $card['title'] }}</h4>
                    <p class="text-[10px] font-medium text-slate-400">{{ $card['desc'] }}</p>
                </div>
                <span class="text-[9px] font-black uppercase tracking-widest text-primary flex items-center gap-1 mt-auto">
                    {{ $card['cta'] }} <i class="bi bi-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </span>
            </a>
            @endforeach
        </div>

    </div>
</div>

@else
{{-- ══ EMPTY STATE (before search) ══ --}}
<div class="bg-slate-50 py-20">
    <div class="max-w-lg mx-auto px-4 text-center">
        <div class="w-24 h-24 rounded-[32px] bg-white border border-slate-100 shadow-sm flex items-center justify-center text-5xl text-slate-200 mx-auto mb-8">
            <i class="bi bi-geo-alt"></i>
        </div>
        <h3 class="text-xl font-black text-slate-300 tracking-tighter uppercase mb-3">No Tracking Data</h3>
        <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest leading-loose max-w-xs mx-auto">
            Enter your Order ID and phone number above to see real-time delivery status.
        </p>
    </div>
</div>
@endisset

@endsection

