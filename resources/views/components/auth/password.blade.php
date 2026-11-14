{{-- Floating-label password input with a show/hide toggle. --}}
@props(['name' => 'password', 'label', 'hint' => null])

@php
    $id = $attributes->get('id', 'field-' . str_replace('_', '-', $name));
    $error = $errors->first($name);
@endphp

<div {{ $attributes->only('class') }} x-data="{ show: false }">
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" :type="show ? 'text' : 'password'" type="password" placeholder=" "
               @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($hint) aria-describedby="{{ $id }}-hint" @endif
               {{ $attributes->except(['class', 'id'])->merge([
                   'class' => 'peer block h-[52px] w-full rounded-[var(--sf-radius-sm)] border bg-white pb-1.5 pl-3 pr-12 pt-5 text-sm text-slate-900 transition-colors focus:outline-none focus:ring-2 '
                       . ($error ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20' : 'border-slate-300 focus:border-brand-600 focus:ring-primary/25'),
               ]) }}>
        <label for="{{ $id }}"
               class="pointer-events-none absolute left-3 top-2 max-w-[calc(100%-4rem)] truncate text-xs text-slate-500 transition-all peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:translate-y-0 peer-focus:text-xs">{{ $label }}</label>
        <button type="button" @click="show = !show" :aria-pressed="show.toString()"
                class="absolute right-1 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 sf-focus">
            <span class="sr-only" x-text="show ? @js(__('Hide password')) : @js(__('Show password'))">{{ __('Show password') }}</span>
            <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
        </button>
    </div>
    @if($error)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $error }}</p>
    @elseif($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
