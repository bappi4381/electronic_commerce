{{-- Checkout input with a floating label: the label sits inside the field and shrinks once there is a value. --}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'optional' => false,
    'hint' => null,
])

@php
    $id = $attributes->get('id', 'checkout-' . str_replace('_', '-', $name));
    $error = $errors->first($name);
@endphp

<div {{ $attributes->only('class') }}>
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" placeholder=" "
               @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($hint) aria-describedby="{{ $id }}-hint" @endif
               {{ $attributes->except(['class', 'id'])->merge([
                   'class' => 'peer block h-[52px] w-full rounded-[var(--sf-radius-sm)] border bg-white px-3 pb-1.5 pt-5 text-sm text-slate-900 transition-colors focus:outline-none focus:ring-2 '
                       . ($error ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20' : 'border-slate-300 focus:border-brand-600 focus:ring-primary/25'),
               ]) }}>
        <label for="{{ $id }}"
               class="pointer-events-none absolute left-3 top-2 max-w-[calc(100%-1.5rem)] truncate text-xs text-slate-500 transition-all peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:translate-y-0 peer-focus:text-xs">
            {{ $label }}@if($optional) <span class="text-slate-400">({{ __('optional') }})</span>@endif
        </label>
    </div>
    @if($error)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $error }}</p>
    @elseif($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
