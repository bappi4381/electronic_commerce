{{--
    Live DD:HH:MM:SS countdown. tone: light (on white) | dark (on navy/red)
    end: Carbon or a date string (read in the store timezone). Dispatches "countdown-ended" (bubbles) at zero.
--}}
@props(['end' => null, 'tone' => 'light'])

@php
    $box = $tone === 'dark'
        ? 'bg-white/15 text-white ring-1 ring-white/20'
        : 'bg-navy-900 text-white';
    $sep = $tone === 'dark' ? 'text-white/70' : 'text-slate-400';
    $units = ['d' => __('Days'), 'h' => __('Hrs'), 'm' => __('Min'), 's' => __('Sec')];
    $endsAt = $end instanceof \Carbon\CarbonInterface
        ? $end
        : (filled($end) ? rescue(fn () => \Carbon\CarbonImmutable::parse($end, config('store.timezone', 'Asia/Dhaka')), null, false) : null);
@endphp

<div x-data="countdown(@js($endsAt?->toIso8601String()), @js(now()->toIso8601String()))" {{ $attributes->merge(['class' => 'flex items-center gap-1']) }}
     role="timer" :aria-label="label" aria-live="off">
    @foreach($units as $key => $label)
        @if(!$loop->first)<span class="{{ $sep }} font-bold" aria-hidden="true">:</span>@endif
        <span class="flex min-w-9 flex-col items-center rounded-[var(--sf-radius-sm)] px-1.5 py-1 {{ $box }}" aria-hidden="true">
            <span class="text-sm font-bold leading-none tabular-nums sm:text-base" x-text="{{ $key }}">00</span>
            <span class="mt-0.5 text-[9px] font-medium uppercase leading-none opacity-75">{{ $label }}</span>
        </span>
    @endforeach
</div>
