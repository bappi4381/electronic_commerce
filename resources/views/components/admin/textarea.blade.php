@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 4,
    'id' => null,
    'hint' => null,
    'required' => false,
    'optional' => false,
    'error' => null,
])

@php
    $errorKey = $error ?? trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $id ??= 'f_' . str_replace(['[', ']', '.'], '_', $name);
    $invalid = $errors->has($errorKey);
@endphp

<x-admin.field :label="$label" :for="$id" :hint="$hint" :error="$errorKey" :required="$required" :optional="$optional">
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}" @if($required) required @endif
              @if($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($hint) aria-describedby="{{ $id }}-hint" @endif
              {{ $attributes->merge(['class' => 'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:outline-none focus:ring-2 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 '
                  . ($invalid ? 'border-red-500 focus:ring-red-500/30' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500/30 dark:border-gray-700')]) }}>{{ old($errorKey, $value) }}</textarea>
</x-admin.field>
