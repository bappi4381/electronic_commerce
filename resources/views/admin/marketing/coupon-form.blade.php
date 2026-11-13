@extends('admin.layouts')
@section('title', $coupon->exists ? 'Edit coupon' : 'New coupon')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header :title="$coupon->exists ? 'Edit coupon ' . $coupon->code : 'New coupon'"
                     :description="$coupon->exists ? 'Used ' . $coupon->used_count . ' ' . \Illuminate\Support\Str::plural('time', $coupon->used_count) . ($coupon->usage_limit ? ' of ' . $coupon->usage_limit : '') . '.' : 'A code customers can enter at checkout.'"
                     :breadcrumbs="['Marketing' => route('marketing.index'), ($coupon->exists ? $coupon->code : 'New coupon') => null]" />

<form method="POST" action="{{ $coupon->exists ? route('coupons.update', $coupon) : route('coupons.store') }}"
      class="mx-auto max-w-3xl space-y-6" x-data="{ type: @js(old('type', $coupon->type ?? 'percent')) }">
    @csrf
    @if($coupon->exists) @method('PUT') @endif

    <x-admin.card title="Discount">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-admin.input name="code" label="Code" required maxlength="50" :value="$coupon->code" placeholder="SAVE20" class="font-mono uppercase"
                           hint="Customers type this at checkout. Not case-sensitive." />
            <x-admin.select name="type" label="Type" required x-model="type" :value="$coupon->type ?? 'percent'"
                            :options="['percent' => 'Percentage (%)', 'fixed' => 'Fixed amount (Tk)']" />
            <div>
                <x-admin.field label="Discount value" for="f_value" error="value" required>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center text-sm text-gray-500 dark:text-gray-400" x-text="type === 'percent' ? '%' : 'Tk'"></span>
                        <input type="number" id="f_value" name="value" step="0.01" min="0.01" required value="{{ old('value', $coupon->value) }}"
                               @error('value') aria-invalid="true" @enderror
                               class="block h-10 w-full rounded-lg border bg-white pl-10 pr-3 text-sm text-gray-900 shadow-xs focus:outline-none focus:ring-2 dark:bg-gray-900 dark:text-gray-100 {{ $errors->has('value') ? 'border-red-500 focus:ring-red-500/30' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500/30 dark:border-gray-700' }}">
                    </div>
                </x-admin.field>
            </div>
            <div x-show="type === 'percent'">
                <x-admin.input name="max_discount" type="number" label="Maximum discount" optional prefix="Tk" step="0.01" min="0" :value="$coupon->max_discount" placeholder="No cap" />
            </div>
            <x-admin.input name="min_order_amount" type="number" label="Minimum order" optional prefix="Tk" step="0.01" min="0" :value="$coupon->min_order_amount"
                           placeholder="No minimum" hint="Compared with the cart subtotal before delivery." />
        </div>
    </x-admin.card>

    <x-admin.card title="Limits & schedule">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-admin.input name="usage_limit" type="number" label="Total usage limit" optional min="1" :value="$coupon->usage_limit" placeholder="Unlimited" />
            <x-admin.input name="per_user_limit" type="number" label="Uses per customer" optional min="1" :value="$coupon->per_user_limit" placeholder="Unlimited"
                           hint="Customers must be logged in to use a coupon with this limit." />
            <x-admin.input name="starts_at" type="datetime-local" label="Starts at" optional :value="$coupon->starts_at?->format('Y-m-d\TH:i')" />
            <x-admin.input name="ends_at" type="datetime-local" label="Ends at" optional :value="$coupon->ends_at?->format('Y-m-d\TH:i')" />
            <div class="md:col-span-2">
                <x-admin.toggle name="status" label="Active" description="Inactive coupons can't be redeemed." :checked="(bool) $coupon->status" />
            </div>
        </div>
    </x-admin.card>

    <div class="flex justify-end gap-2">
        <x-admin.button variant="secondary" :href="route('marketing.index')">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">{{ $coupon->exists ? 'Save changes' : 'Create coupon' }}</x-admin.button>
    </div>
</form>
@endsection
