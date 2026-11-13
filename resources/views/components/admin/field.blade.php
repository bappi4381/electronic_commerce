{{-- Label, hint and validation error around any control. "error" is the validation key (dot notation). --}}
@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'optional' => false,
])

@php($message = $error ? $errors->first($error) : null)

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)<span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>@endif
            @if($optional)<span class="font-normal text-gray-400 dark:text-gray-500">(optional)</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if($message)
        <p @if($for) id="{{ $for }}-error" @endif class="flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}
        </p>
    @elseif($hint)
        <p @if($for) id="{{ $for }}-hint" @endif class="text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>
