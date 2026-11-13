{{--
    Coloured status pill.
    type="order" / "payment" use the enum labels; otherwise the status is humanised.
    tone overrides the colour: gray | green | yellow | red | blue | indigo | orange | brand
--}}
@props([
    'status' => null,
    'type' => null,
    'tone' => null,
    'label' => null,
    'dot' => true,
])

@php
    $value = strtolower((string) $status);

    $tones = [
        'order' => [
            'pending' => 'yellow', 'confirmed' => 'brand', 'processing' => 'blue', 'shipped' => 'indigo',
            'delivered' => 'green', 'cancelled' => 'red', 'returned' => 'orange', 'refunded' => 'gray',
        ],
        'payment' => [
            'paid' => 'green', 'success' => 'green', 'pending' => 'yellow', 'unpaid' => 'gray', 'failed' => 'red',
            'cancelled' => 'red', 'partially_refunded' => 'orange', 'refunded' => 'gray',
        ],
        'generic' => [
            'active' => 'green', 'approved' => 'green', 'published' => 'green', 'success' => 'green', 'completed' => 'green', 'enabled' => 'green', 'in stock' => 'green',
            'inactive' => 'gray', 'draft' => 'gray', 'unpublished' => 'gray', 'disabled' => 'gray', 'archived' => 'gray', 'unsubscribed' => 'gray',
            'pending' => 'yellow', 'low stock' => 'yellow', 'scheduled' => 'blue',
            'rejected' => 'red', 'failed' => 'red', 'blocked' => 'red', 'expired' => 'red', 'out of stock' => 'red',
        ],
    ];

    $tone ??= $tones[$type ?? 'generic'][$value] ?? $tones['generic'][$value] ?? 'gray';

    $label ??= match ($type) {
        'order' => \App\Enums\OrderStatus::tryFrom($value)?->label(),
        'payment' => \App\Enums\PaymentStatus::tryFrom($value)?->label(),
        default => null,
    } ?? \Illuminate\Support\Str::of($value)->replace(['_', '-'], ' ')->ucfirst();

    $classes = [
        'gray'   => 'bg-gray-100 text-gray-700 ring-gray-500/20 dark:bg-gray-500/10 dark:text-gray-300 dark:ring-gray-400/20',
        'green'  => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20',
        'yellow' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-300 dark:ring-yellow-500/20',
        'red'    => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20',
        'blue'   => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/20',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-500/20',
        'brand'  => 'bg-brand-50 text-brand-700 ring-brand-600/20 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20',
    ][$tone] ?? '';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>
    @if($dot)<span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif
    {{ $label }}
</span>
