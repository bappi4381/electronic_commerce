{{-- Accessible switch. Always submits: "1" when on, "0" when off. --}}
@props([
    'name',
    'label',
    'checked' => false,
    'description' => null,
    'id' => null,
])

@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id ??= 'f_' . str_replace(['[', ']', '.'], '_', $name);
    $on = (bool) old($key, $checked);
@endphp

<div class="flex items-start justify-between gap-4" x-data="{ on: @js($on) }">
    <label for="{{ $id }}" class="text-sm">
        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $label }}</span>
        @if($description)<span class="block text-gray-500 dark:text-gray-400">{{ $description }}</span>@endif
    </label>
    <input type="hidden" name="{{ $name }}" :value="on ? 1 : 0" value="{{ $on ? 1 : 0 }}">
    <button type="button" id="{{ $id }}" role="switch" :aria-checked="on.toString()" aria-checked="{{ $on ? 'true' : 'false' }}" @click="on = !on"
            {{ $attributes->merge(['class' => 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-ring']) }}
            :class="on ? 'bg-brand-600 dark:bg-brand-500' : 'bg-gray-200 dark:bg-gray-700'">
        <span class="sr-only">{{ $label }}</span>
        <span aria-hidden="true" class="pointer-events-none inline-block size-5 rounded-full bg-white shadow-sm ring-0 transition-transform"
              :class="on ? 'translate-x-5' : 'translate-x-0'"></span>
    </button>
</div>
