{{-- Password input with a show/hide toggle. Never repopulated from old input. --}}
@props([
    'name' => 'password',
    'label' => 'Password',
    'id' => null,
    'hint' => null,
    'required' => false,
    'error' => null,
])

@php
    $errorKey = $error ?? $name;
    $id ??= 'f_' . $name;
    $invalid = $errors->has($errorKey);
@endphp

<x-admin.field :label="$label" :for="$id" :hint="$hint" :error="$errorKey" :required="$required">
    <div class="relative" x-data="{ visible: false }">
        <input :type="visible ? 'text' : 'password'" type="password" name="{{ $name }}" id="{{ $id }}" @if($required) required @endif
               @if($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($hint) aria-describedby="{{ $id }}-hint" @endif
               {{ $attributes->merge(['class' => 'block h-10 w-full rounded-lg border bg-white pl-3 pr-11 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:outline-none focus:ring-2 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 '
                   . ($invalid ? 'border-red-500 focus:ring-red-500/30' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500/30 dark:border-gray-700')]) }}>
        <button type="button" @click="visible = !visible" :aria-pressed="visible.toString()"
                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-gray-400 hover:text-gray-600 focus-ring dark:hover:text-gray-200">
            <span class="sr-only" x-text="visible ? 'Hide password' : 'Show password'">Show password</span>
            <i class="bi" :class="visible ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
        </button>
    </div>
</x-admin.field>
