{{--
    Text-like input with label, hint, prefix/suffix and validation state. Repopulates from old() by default.
    Extra attributes (min, step, placeholder, x-model, …) are passed to the <input>.
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'id' => null,
    'hint' => null,
    'required' => false,
    'optional' => false,
    'prefix' => null,
    'suffix' => null,
    'error' => null,
    'old' => true,
])

@php
    $errorKey = $error ?? trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $id ??= 'f_' . str_replace(['[', ']', '.'], '_', $name);
    $invalid = $errors->has($errorKey);
    $current = $type === 'password' || $type === 'file' ? null : ($old ? old($errorKey, $value) : $value);
    $control = 'block w-full rounded-lg border bg-white text-sm text-gray-900 shadow-xs placeholder:text-gray-400 transition-colors '
        . 'focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 '
        . 'dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:disabled:bg-gray-800 '
        . ($type === 'file'
            ? 'p-1.5 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-gray-800 dark:file:text-gray-200 '
            : 'h-10 px-3 ')
        . ($invalid
            ? 'border-red-500 focus:border-red-500 focus:ring-red-500/30 dark:border-red-500'
            : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500/30 dark:border-gray-700')
        . ($prefix ? ' pl-10' : '') . ($suffix ? ' pr-12' : '');
@endphp

<x-admin.field :label="$label" :for="$id" :hint="$hint" :error="$errorKey" :required="$required" :optional="$optional">
    <div class="relative">
        @if($prefix)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                @if(str_starts_with($prefix, 'bi-'))<i class="bi {{ $prefix }}" aria-hidden="true"></i>@else{{ $prefix }}@endif
            </span>
        @endif
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
               @if($current !== null && !is_array($current)) value="{{ $current }}" @endif
               @if($required) required @endif
               @if($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($hint) aria-describedby="{{ $id }}-hint" @endif
               {{ $attributes->merge(['class' => $control]) }}>
        @if($suffix)
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm text-gray-500 dark:text-gray-400">{{ $suffix }}</span>
        @endif
    </div>
</x-admin.field>
