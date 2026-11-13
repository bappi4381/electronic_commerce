{{-- Checkbox with label and optional description. "unchecked" sends a hidden fallback value (e.g. 0). --}}
@props([
    'name',
    'label',
    'value' => '1',
    'checked' => false,
    'description' => null,
    'unchecked' => null,
    'id' => null,
])

@php
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $id ??= 'f_' . str_replace(['[', ']', '.'], '_', $name) . (str_ends_with($name, '[]') ? '_' . $value : '');
    $old = old($key);
    $isChecked = $old !== null ? (is_array($old) ? in_array((string) $value, array_map('strval', $old), true) : (string) $old === (string) $value) : $checked;
@endphp

<div class="flex items-start gap-3">
    @if($unchecked !== null)<input type="hidden" name="{{ $name }}" value="{{ $unchecked }}">@endif
    <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @checked($isChecked)
           {{ $attributes->merge(['class' => 'mt-0.5 size-4 shrink-0 rounded border-gray-300 text-brand-600 focus:ring-2 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-800']) }}>
    <label for="{{ $id }}" class="text-sm">
        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $label }}</span>
        @if($description)<span class="block text-gray-500 dark:text-gray-400">{{ $description }}</span>@endif
    </label>
</div>
