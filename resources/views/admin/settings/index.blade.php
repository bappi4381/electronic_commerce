@extends('admin.layouts')
@section('title', 'Settings')
@section('ui-v2', '1')

@section('content')
@php
    $tabs = [
        'store' => ['label' => 'Store', 'icon' => 'bi-shop'],
        'shipping' => ['label' => 'Shipping & payments', 'icon' => 'bi-truck'],
        'contact' => ['label' => 'Contact & social', 'icon' => 'bi-geo-alt'],
        'footer' => ['label' => 'Footer', 'icon' => 'bi-layout-text-window-reverse'],
        'campaigns' => ['label' => 'Campaigns', 'icon' => 'bi-lightning-charge'],
    ];
    $tabOf = fn (string $key) => match (true) {
        in_array($key, ['site_name', 'logo', 'remove_logo'], true) => 'store',
        str_starts_with($key, 'shipping_') || str_starts_with($key, 'payment_') || $key === 'free_shipping_threshold' => 'shipping',
        str_starts_with($key, 'contact_') || str_ends_with($key, '_url') => 'contact',
        $key === 'footer_text' => 'footer',
        str_starts_with($key, 'flash_deal_') => 'campaigns',
        default => 'store',
    };
    $errorTabs = collect($errors->keys())->mapWithKeys(fn ($key) => [$tabOf($key) => true])->all();
    $firstErrorTab = $errors->any() ? $tabOf($errors->keys()[0]) : null;

    $zoneRows = array_values(old('shipping_zones', $zones));
    $codOn = (bool) old('payment_cod_enabled', in_array('cod', $paymentMethods, true));
    $onlineOn = (bool) old('payment_online_enabled', in_array('online', $paymentMethods, true));
    $flashEnd = !empty($settings['flash_deal_end_time']) ? date('Y-m-d\TH:i', strtotime($settings['flash_deal_end_time'])) : null;
    $canEdit = auth()->user()->can('settings.edit');
@endphp

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" id="settingsForm"
      x-data="{ tab: @js($firstErrorTab) || (Object.keys(@js($tabs)).includes(location.hash.slice(1)) ? location.hash.slice(1) : 'store') }"
      x-init="$watch('tab', value => history.replaceState(null, '', '#' + value))">
    @csrf

    <x-admin.page-header title="Settings" description="Store identity, checkout, contact details and campaigns. Clearing a field removes the value.">
        @if($canEdit)
            <x-admin.button type="submit" icon="bi-check-lg">Save settings</x-admin.button>
        @endif
    </x-admin.page-header>

    <x-admin.tabs :tabs="$tabs" :errors="$errorTabs" label="Settings sections" class="mb-6" />

    <fieldset @disabled(!$canEdit) class="min-w-0">
        {{-- Store --}}
        <x-admin.tab-panel name="store">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <x-admin.card title="Store identity" class="xl:col-span-2">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <x-admin.input name="site_name" label="Store name" :value="$settings['site_name'] ?? ''" placeholder="e.g. ONEMALL" />
                        <div class="space-y-3">
                            <div class="flex items-center gap-4">
                                @if(!empty($settings['logo']))
                                    <span class="flex size-14 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-900 p-2 dark:border-gray-700">
                                        <img src="{{ asset('storage/' . $settings['logo']) }}" alt="Current logo" class="max-h-full max-w-full object-contain">
                                    </span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <x-admin.input name="logo" type="file" label="Logo" optional accept="image/*" />
                                </div>
                            </div>
                            @if(!empty($settings['logo']))
                                <x-admin.checkbox name="remove_logo" label="Remove the current logo" />
                            @endif
                        </div>
                    </div>
                </x-admin.card>
                <x-admin.alert tone="info" title="Tip" class="self-start">
                    Use a logo with a transparent background (PNG or SVG) so it looks right on light and dark backgrounds.
                </x-admin.alert>
            </div>
        </x-admin.tab-panel>

        {{-- Shipping & payments --}}
        <x-admin.tab-panel name="shipping">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-admin.card title="Shipping zones" description="A delivery charge per city or area.">
                        <div x-data="{ zones: {{ \Illuminate\Support\Js::from($zoneRows) }} }" class="space-y-3">
                            <template x-for="(zone, index) in zones" :key="index">
                                <div class="flex items-center gap-2">
                                    <label class="sr-only" :for="`zone-name-${index}`">Zone name</label>
                                    <input type="text" :id="`zone-name-${index}`" :name="`shipping_zones[${index}][name]`" x-model="zone.name" maxlength="60" placeholder="City or area, e.g. Dhaka"
                                           class="block h-10 min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-xs placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900">
                                    <div class="relative w-36">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-9 items-center justify-center text-sm text-gray-500 dark:text-gray-400">Tk</span>
                                        <label class="sr-only" :for="`zone-charge-${index}`">Charge</label>
                                        <input type="number" :id="`zone-charge-${index}`" :name="`shipping_zones[${index}][charge]`" x-model="zone.charge" step="0.01" min="0" placeholder="0.00"
                                               class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm tabular-nums shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900">
                                    </div>
                                    <button type="button" @click="zones.splice(index, 1)"
                                            class="flex size-10 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 focus-ring dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                        <span class="sr-only">Remove zone</span><i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </template>
                            <p x-show="!zones.length" class="text-sm text-gray-500 dark:text-gray-400">No zones. Every address pays the default charge below.</p>
                            @error('shipping_zones.*')<p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            <x-admin.button variant="secondary" size="sm" icon="bi-plus-lg" @click="zones.push({ name: '', charge: '' })">Add zone</x-admin.button>
                        </div>
                        <div class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-200 pt-6 md:grid-cols-2 dark:border-gray-800">
                            <x-admin.input name="shipping_default_charge" type="number" label="Default charge" required prefix="Tk" step="0.01" min="0" :value="$defaultCharge"
                                           hint="Applies to every area without its own zone." />
                            <x-admin.input name="free_shipping_threshold" type="number" label="Free shipping from" optional prefix="Tk" step="0.01" min="0"
                                           :value="$settings['free_shipping_threshold'] ?? ''" placeholder="Leave empty to disable" />
                        </div>
                    </x-admin.card>

                    <x-admin.card title="Payment methods" description="Disabled methods are hidden and rejected at checkout.">
                        <div class="space-y-5">
                            <x-admin.toggle name="payment_cod_enabled" label="Cash on delivery" description="Customer pays the courier on delivery." :checked="$codOn" />
                            <x-admin.toggle name="payment_online_enabled" label="Online payment" description="Cards and mobile banking via SSLCommerz." :checked="$onlineOn" />
                            @error('payment_cod_enabled')<p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    </x-admin.card>
                </div>

                <x-admin.card title="How checkout uses this" class="self-start">
                    <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <li class="flex gap-2"><i class="bi bi-check-circle-fill mt-0.5 text-green-600 dark:text-green-400" aria-hidden="true"></i> Customers pick a zone as their delivery city; "Other" uses the default charge.</li>
                        <li class="flex gap-2"><i class="bi bi-check-circle-fill mt-0.5 text-green-600 dark:text-green-400" aria-hidden="true"></i> Orders whose items total (after coupons) reaches the threshold ship free.</li>
                        <li class="flex gap-2"><i class="bi bi-check-circle-fill mt-0.5 text-green-600 dark:text-green-400" aria-hidden="true"></i> At least one payment method must stay enabled.</li>
                    </ul>
                </x-admin.card>
            </div>
        </x-admin.tab-panel>

        {{-- Contact & social --}}
        <x-admin.tab-panel name="contact">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <x-admin.card title="Contact & support" class="xl:col-span-2">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-admin.input name="contact_email" type="email" label="Support email" optional :value="$settings['contact_email'] ?? ''" placeholder="support@example.com" />
                        <x-admin.input name="contact_phone" label="Hotline" optional :value="$settings['contact_phone'] ?? ''" placeholder="+880 1XXX-XXXXXX" />
                        <div class="md:col-span-2">
                            <x-admin.textarea name="contact_address" label="Address" optional rows="3" :value="$settings['contact_address'] ?? ''" placeholder="Full office or store address" />
                        </div>
                    </div>
                </x-admin.card>
                <x-admin.card title="Social links" description="Shown as icons in the storefront footer." class="self-start">
                    <div class="space-y-4">
                        <x-admin.input name="facebook_url" type="url" label="Facebook" optional prefix="bi-facebook" :value="$settings['facebook_url'] ?? ''" placeholder="https://facebook.com/yourpage" />
                        <x-admin.input name="twitter_url" type="url" label="X (Twitter)" optional prefix="bi-twitter-x" :value="$settings['twitter_url'] ?? ''" placeholder="https://x.com/yourhandle" />
                        <x-admin.input name="instagram_url" type="url" label="Instagram" optional prefix="bi-instagram" :value="$settings['instagram_url'] ?? ''" placeholder="https://instagram.com/yourprofile" />
                    </div>
                </x-admin.card>
            </div>
        </x-admin.tab-panel>

        {{-- Footer --}}
        <x-admin.tab-panel name="footer">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <x-admin.card title="Footer" class="xl:col-span-2">
                    <x-admin.textarea name="footer_text" label="About text" optional rows="5" :value="$settings['footer_text'] ?? ''"
                                      placeholder="A short description of your company for the footer…" hint="Shown at the bottom left of every storefront page." />
                </x-admin.card>
            </div>
        </x-admin.tab-panel>

        {{-- Campaigns --}}
        <x-admin.tab-panel name="campaigns">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <x-admin.card title="Flash deal campaign" class="xl:col-span-2">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-admin.input name="flash_deal_title" label="Campaign title" optional :value="$settings['flash_deal_title'] ?? ''" placeholder="e.g. Eid Special" />
                        <x-admin.input name="flash_deal_end_time" type="datetime-local" label="Ends at" optional :value="$flashEnd" />
                    </div>
                </x-admin.card>
                <x-admin.alert tone="info" title="Which products appear?" class="self-start">
                    Products marked as flash deals with a discount show in the storefront's flash deals section, with this title and countdown.
                    @can('marketing.view')
                        <a href="{{ route('marketing.index') }}" class="mt-2 block rounded font-medium underline focus-ring">Manage discounts in Marketing</a>
                    @endcan
                </x-admin.alert>
            </div>
        </x-admin.tab-panel>
    </fieldset>

    @if($canEdit)
        <div class="mt-6 flex justify-end">
            <x-admin.button type="submit" icon="bi-check-lg">Save settings</x-admin.button>
        </div>
    @endif
</form>
@endsection
