{{-- Select with label/hint/error. Pass :options (value => label) or <option> elements in the slot. --}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
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
    $selected = (string) old($errorKey, $value);
@endphp

<x-admin.field :label="$label" :for="$id" :hint="$hint" :error="$errorKey" :required="$required" :optional="$optional">
    <select name="{{ $name }}" id="{{ $id }}" @if($required) required @endif
            @if($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($hint) aria-describedby="{{ $id }}-hint" @endif
            {{ $attributes->merge(['class' => 'block h-10 w-full rounded-lg border bg-white pl-3 pr-9 text-sm text-gray-900 shadow-xs focus:outline-none focus:ring-2 dark:bg-gray-900 dark:text-gray-100 '
                . ($invalid ? 'border-red-500 focus:ring-red-500/30' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500/30 dark:border-gray-700')]) }}>
        @if($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
</x-admin.field>
