{{-- Pending → Confirmed → Processing → Shipped → Delivered, with the date each step was reached. --}}
@props(['order'])

@use('App\Enums\OrderStatus')

@php
    $steps = OrderStatus::fulfilmentSteps();
    $status = $order->statusEnum()?->customerFacing();
    $currentIndex = array_search($status, $steps, true);
    $stopped = $currentIndex === false;
    $reachedAt = $order->relationLoaded('statusHistories')
        ? $order->statusHistories->groupBy('to_status')->map(fn ($rows) => $rows->last()->created_at)
        : collect();
@endphp

<div {{ $attributes }}>
    @if($stopped && $status)
        <div class="mb-5 flex items-center gap-3 rounded-[var(--sf-radius-sm)] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <i class="bi {{ $status->icon() }} text-lg" aria-hidden="true"></i>
            <p><strong>{{ __($status->label()) }}.</strong> {{ __($status->customerMessage() ?? '') }}</p>
        </div>
    @endif

    <ol class="grid grid-cols-5" aria-label="{{ __('Delivery progress') }}">
        @foreach($steps as $i => $step)
            @php
                $reached = !$stopped && $i <= $currentIndex;
                $active = !$stopped && $i === $currentIndex;
                $date = $reachedAt[$step->value] ?? ($i === 0 ? $order->created_at : null);
            @endphp
            <li class="relative flex flex-col items-center text-center" @if($active) aria-current="step" @endif>
                @if($i > 0)
                    <span @class(['absolute right-1/2 top-4 h-0.5 w-full -translate-y-1/2', 'bg-brand-600' => $reached, 'bg-slate-200' => !$reached]) aria-hidden="true"></span>
                @endif
                <span @class(['relative z-10 flex size-8 items-center justify-center rounded-full text-sm',
                              'bg-brand-600 text-white ring-4 ring-brand-100' => $active,
                              'bg-brand-600 text-white' => $reached && !$active,
                              'border-2 border-slate-200 bg-white text-slate-300' => !$reached]) aria-hidden="true">
                    <i class="bi {{ $reached && !$active ? 'bi-check-lg' : $step->icon() }}"></i>
                </span>
                <span @class(['mt-2 text-[11px] font-medium leading-tight sm:text-xs', 'text-slate-900' => $reached, 'text-slate-400' => !$reached])>{{ __($step->label()) }}</span>
                @if($reached && $date)
                    <span class="mt-0.5 hidden text-[11px] text-slate-500 sm:block">{{ $date->format('d M') }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</div>
