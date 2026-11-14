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
        'tracking' => ['label' => 'Tracking', 'icon' => 'bi-graph-up-arrow'],
        'courier' => ['label' => 'Courier', 'icon' => 'bi-box-seam'],
        'fraud' => ['label' => 'Fraud protection', 'icon' => 'bi-shield-check'],
    ];
    $tabOf = fn (string $key) => match (true) {
        in_array($key, ['site_name', 'logo', 'remove_logo'], true) => 'store',
        str_starts_with($key, 'shipping_') || str_starts_with($key, 'payment_') || $key === 'free_shipping_threshold' => 'shipping',
        str_starts_with($key, 'contact_') || str_ends_with($key, '_url') => 'contact',
        $key === 'footer_text' => 'footer',
        str_starts_with($key, 'flash_deal_') => 'campaigns',
        str_starts_with($key, 'fb_') || $key === 'remove_fb_capi_access_token' => 'tracking',
        (bool) preg_match('/^(remove_|generate_)?courier_/', $key) => 'courier',
        (bool) preg_match('/^(remove_)?fraud_/', $key) => 'fraud',
        default => 'store',
    };
    $errorTabs = collect($errors->keys())->mapWithKeys(fn ($key) => [$tabOf($key) => true])->all();
    $firstErrorTab = $errors->any() ? $tabOf($errors->keys()[0]) : null;

    $zoneRows = array_values(old('shipping_zones', $zones));
    $codOn = (bool) old('payment_cod_enabled', in_array('cod', $paymentMethods, true));
    $onlineOn = (bool) old('payment_online_enabled', in_array('online', $paymentMethods, true));
    $canEdit = auth()->user()->can('settings.edit');
    $fbCapi = app(\App\Services\FacebookCapiService::class);
    $fbSources = collect(['fb_pixel_id', 'fb_capi_access_token', 'fb_test_event_code'])->mapWithKeys(fn ($key) => [$key => $fbCapi->source($key)])->all();
    $fbTokenPlaceholder = $fbSources['fb_capi_access_token'] === 'admin' ? 'Saved (hidden). Paste a new token to replace it.' : 'Paste the access token';
    $fbTokenHint = match ($fbSources['fb_capi_access_token']) {
        'admin' => 'Stored encrypted. Leave blank to keep the saved token.',
        'env' => 'Empty here, so the token from .env is used.',
        default => 'Events Manager › Settings › Conversions API › Generate access token. Stored encrypted.',
    };
    $fbStatusRows = collect(['fb_pixel_id' => 'Pixel ID', 'fb_capi_access_token' => 'Access token', 'fb_test_event_code' => 'Test event code'])
        ->map(fn ($label, $key) => ['label' => $label] + match (true) {
            $key === 'fb_test_event_code' && $fbSources[$key] !== null => ['tone' => 'yellow', 'text' => 'Test mode on'],
            $fbSources[$key] === 'admin' => ['tone' => 'green', 'text' => 'Set'],
            $fbSources[$key] === 'env' => ['tone' => 'blue', 'text' => 'From .env'],
            default => ['tone' => 'gray', 'text' => 'Not set'],
        });

    $courierSources = collect(\App\Services\Courier\CourierSettings::FIELDS)->map(fn ($field, $key) => \App\Services\Courier\CourierSettings::source($key))->all();
    $secretPlaceholder = fn (string $key) => $courierSources[$key] === 'admin' ? 'Saved (hidden). Paste a new value to replace it.' : '';
    $secretHint = fn (string $key, string $where) => match ($courierSources[$key]) {
        'admin' => 'Stored encrypted. Leave blank to keep the saved value.',
        'env' => 'Empty here, so the value from .env is used.',
        default => $where . ' Stored encrypted.',
    };
    $plainHint = fn (string $key, string $where) => $courierSources[$key] === 'env' ? 'Empty here, so the value from .env is used.' : $where;
    $courierReady = [
        'steadfast' => app(\App\Services\Courier\SteadfastService::class)->configured(),
        'pathao' => app(\App\Services\Courier\PathaoService::class)->configured(),
    ];
    $courierStatusRows = [
        'Steadfast' => $courierReady['steadfast'],
        'Steadfast webhook' => filled(config('services.steadfast.webhook_secret')),
        'Pathao' => $courierReady['pathao'],
        'Pathao webhook' => filled(config('services.pathao.webhook_secret')),
    ];

    $fraudKeySaved = filled($settings['fraud_bdcourier_api_key'] ?? null);
    $fraudKeyFromEnv = !$fraudKeySaved && filled(config('fraud.drivers.bdcourier.api_key'));
@endphp

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" id="settingsForm"
      x-data="{ tab: @js($firstErrorTab) || (Object.keys(@js($tabs)).includes(location.hash.slice(1)) ? location.hash.slice(1) : 'store'), dirty: false }"
      @input="dirty = true" @change="dirty = true" @click="if ($event.target.closest('[role=switch]')) dirty = true"
      x-init="$watch('tab', value => history.replaceState(null, '', '#' + value))">
    @csrf

    <x-admin.page-header title="Settings" description="Store identity, checkout, contact details and campaigns. Clearing a field removes the value." />

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
                                    <div class="relative hidden w-36 sm:block">
                                        <i class="bi bi-clock pointer-events-none absolute inset-y-0 left-0 flex w-9 items-center justify-center text-sm text-gray-400" aria-hidden="true"></i>
                                        <label class="sr-only" :for="`zone-eta-${index}`">Delivery time</label>
                                        <input type="text" :id="`zone-eta-${index}`" :name="`shipping_zones[${index}][eta]`" x-model="zone.eta" maxlength="40" placeholder="1-2 days"
                                               class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm shadow-xs placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900">
                                    </div>
                                    <button type="button" @click="zones.splice(index, 1)"
                                            class="flex size-10 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 focus-ring dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                        <span class="sr-only">Remove zone</span><i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </template>
                            <p x-show="!zones.length" class="text-sm text-gray-500 dark:text-gray-400">No zones. Every address pays the default charge below.</p>
                            @error('shipping_zones.*')<p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            <x-admin.button variant="secondary" size="sm" icon="bi-plus-lg" @click="zones.push({ name: '', charge: '', eta: '' })">Add zone</x-admin.button>
                        </div>
                        <div class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-200 pt-6 md:grid-cols-3 dark:border-gray-800">
                            <x-admin.input name="shipping_default_charge" type="number" label="Default charge" required prefix="Tk" step="0.01" min="0" :value="$defaultCharge"
                                           hint="Applies to every area without its own zone." />
                            <x-admin.input name="shipping_default_eta" label="Default delivery time" optional maxlength="40" prefix="bi-clock"
                                           :value="$settings['shipping_default_eta'] ?? ''" placeholder="e.g. 3-5 days" hint="Shown at checkout for other areas." />
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
                        <li class="flex gap-2"><i class="bi bi-check-circle-fill mt-0.5 text-green-600 dark:text-green-400" aria-hidden="true"></i> Delivery times appear next to the charge in the checkout's delivery method.</li>
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
                    @include('admin.marketing.partials.flash-campaign', ['canEdit' => $canEdit])
                </x-admin.card>
                <x-admin.alert tone="info" title="Which products appear?" class="self-start">
                    Products marked as flash deals with a discount show in the storefront's flash deals section, with this title and countdown.
                    @can('marketing.view')
                        <a href="{{ route('marketing.index') }}" class="mt-2 block rounded font-medium underline focus-ring">Manage discounts in Marketing</a>
                    @endcan
                </x-admin.alert>
            </div>
        </x-admin.tab-panel>

        {{-- Tracking --}}
        <x-admin.tab-panel name="tracking">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <x-admin.card title="Meta (Facebook) Pixel & Conversions API" description="Browser Pixel and server events share an event ID, so Meta counts each action once." class="xl:col-span-2">
                    <div class="space-y-5">
                        <x-admin.toggle name="fb_pixel_enabled" label="Browser Pixel" description="PageView, ViewContent, AddToCart, InitiateCheckout and Purchase from the shopper's browser."
                                        :checked="($settings['fb_pixel_enabled'] ?? '1') === '1'" />
                        <x-admin.toggle name="fb_capi_enabled" label="Conversions API (server)" description="The same events sent from the server through the queue, so ad blockers cannot hide them."
                                        :checked="($settings['fb_capi_enabled'] ?? '1') === '1'" />
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-200 pt-6 md:grid-cols-2 dark:border-gray-800">
                        <x-admin.input name="fb_pixel_id" label="Pixel ID" optional inputmode="numeric" autocomplete="off" maxlength="25" prefix="bi-meta"
                                       :value="$settings['fb_pixel_id'] ?? ''" placeholder="e.g. 1234567890123456"
                                       :hint="$fbSources['fb_pixel_id'] === 'env' ? 'Empty here, so the value from .env is used.' : 'Events Manager › Data sources › your Pixel.'" />
                        <x-admin.input name="fb_test_event_code" label="Test event code" optional autocomplete="off" maxlength="50" prefix="bi-bug"
                                       :value="$settings['fb_test_event_code'] ?? ''" placeholder="e.g. TEST12345"
                                       :hint="$fbSources['fb_test_event_code'] === 'env' ? 'Empty here, so the value from .env is used. Clear both before going live.' : 'Events Manager › Test events. Clear it before going live.'" />
                        <div class="md:col-span-2">
                            <x-admin.input name="fb_capi_access_token" type="password" label="Conversions API access token" optional autocomplete="new-password" maxlength="1000" prefix="bi-key"
                                           :placeholder="$fbTokenPlaceholder" :hint="$fbTokenHint" />
                            @if($fbSources['fb_capi_access_token'] === 'admin')
                                <div class="mt-2"><x-admin.checkbox name="remove_fb_capi_access_token" label="Remove the saved token" /></div>
                            @endif
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Status" class="self-start">
                    <dl class="space-y-3 text-sm">
                        @foreach($fbStatusRows as $row)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ $row['label'] }}</dt>
                                <dd><x-admin.status-badge :tone="$row['tone']" :label="$row['text']" /></dd>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Queue</dt>
                            <dd class="font-mono">{{ config('queue.default') }}</dd>
                        </div>
                    </dl>
                    @if($canEdit)
                        <x-admin.button type="submit" form="fb-test-form" variant="secondary" icon="bi-send" class="mt-5 w-full">Send test event</x-admin.button>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Uses the saved values, so save first. The event appears in Events Manager › Test events.</p>
                    @endif
                    <p class="mt-4 border-t border-gray-200 pt-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        Server events need a running queue worker (<code>php artisan queue:work</code>).
                    </p>
                </x-admin.card>
            </div>
        </x-admin.tab-panel>

        {{-- Courier --}}
        <x-admin.tab-panel name="courier">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-admin.card title="Steadfast" description="Steadfast portal (portal.packzy.com) › API.">
                        @if($canEdit)
                            <x-slot:actions>
                                <x-admin.button type="submit" form="courier-test-steadfast" variant="secondary" size="sm" icon="bi-plug">Check connection</x-admin.button>
                            </x-slot:actions>
                        @endif
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <x-admin.input name="courier_steadfast_api_key" label="API key" optional autocomplete="off" maxlength="255" prefix="bi-key"
                                           :value="$settings['courier_steadfast_api_key'] ?? ''" :hint="$plainHint('courier_steadfast_api_key', 'Shown as Api-Key in the Steadfast portal.')" />
                            <div>
                                <x-admin.input name="courier_steadfast_secret_key" type="password" label="Secret key" optional autocomplete="new-password" maxlength="255" prefix="bi-shield-lock"
                                               :placeholder="$secretPlaceholder('courier_steadfast_secret_key')" :hint="$secretHint('courier_steadfast_secret_key', 'Shown as Secret-Key in the Steadfast portal.')" />
                                @if($courierSources['courier_steadfast_secret_key'] === 'admin')
                                    <div class="mt-2"><x-admin.checkbox name="remove_courier_steadfast_secret_key" label="Remove the saved secret key" /></div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-800">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Webhook (automatic delivery updates)</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">In the Steadfast portal's webhook settings, paste the callback URL and the token below as the Bearer token.</p>
                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-admin.copy-field label="Callback URL" :value="route('webhooks.steadfast')" />
                                <x-admin.copy-field label="Bearer token" :value="$canEdit ? config('services.steadfast.webhook_secret') : null" secret
                                                    :empty="$canEdit ? 'Not set: generate one below' : 'Hidden'" />
                                <div class="md:col-span-2">
                                    <x-admin.input name="courier_steadfast_webhook_secret" type="password" label="Use my own token" optional autocomplete="new-password" maxlength="255"
                                                   placeholder="At least 16 characters" :hint="$courierSources['courier_steadfast_webhook_secret'] === 'env' ? 'Empty here, so the token from .env is used.' : 'Leave blank to keep the current token.'" />
                                    <div class="mt-2"><x-admin.checkbox name="generate_courier_steadfast_webhook_secret" label="Generate a new random token (update it in the Steadfast portal afterwards)" /></div>
                                </div>
                            </div>
                        </div>
                    </x-admin.card>

                    <x-admin.card title="Pathao" description="Pathao merchant panel › Developers API.">
                        @if($canEdit)
                            <x-slot:actions>
                                <x-admin.button type="submit" form="courier-test-pathao" variant="secondary" size="sm" icon="bi-plug">Check connection</x-admin.button>
                            </x-slot:actions>
                        @endif
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <x-admin.select name="courier_pathao_mode" label="Environment" :options="['live' => 'Live', 'sandbox' => 'Sandbox (testing)']"
                                            :value="$settings['courier_pathao_mode'] ?? \App\Services\Courier\CourierSettings::pathaoMode()" />
                            <x-admin.input name="courier_pathao_store_id" label="Store ID" optional inputmode="numeric" autocomplete="off" maxlength="20"
                                           :value="$settings['courier_pathao_store_id'] ?? ''" :hint="$plainHint('courier_pathao_store_id', 'Merchant panel › Stores.')" />
                            <x-admin.input name="courier_pathao_client_id" label="Client ID" optional autocomplete="off" maxlength="255"
                                           :value="$settings['courier_pathao_client_id'] ?? ''" :hint="$plainHint('courier_pathao_client_id', '')" />
                            <div>
                                <x-admin.input name="courier_pathao_client_secret" type="password" label="Client secret" optional autocomplete="new-password" maxlength="255"
                                               :placeholder="$secretPlaceholder('courier_pathao_client_secret')" :hint="$secretHint('courier_pathao_client_secret', '')" />
                                @if($courierSources['courier_pathao_client_secret'] === 'admin')
                                    <div class="mt-2"><x-admin.checkbox name="remove_courier_pathao_client_secret" label="Remove the saved client secret" /></div>
                                @endif
                            </div>
                            <x-admin.input name="courier_pathao_username" label="Login email" optional autocomplete="off" maxlength="255"
                                           :value="$settings['courier_pathao_username'] ?? ''" :hint="$plainHint('courier_pathao_username', 'The merchant account login.')" />
                            <div>
                                <x-admin.input name="courier_pathao_password" type="password" label="Login password" optional autocomplete="new-password" maxlength="255"
                                               :placeholder="$secretPlaceholder('courier_pathao_password')" :hint="$secretHint('courier_pathao_password', '')" />
                                @if($courierSources['courier_pathao_password'] === 'admin')
                                    <div class="mt-2"><x-admin.checkbox name="remove_courier_pathao_password" label="Remove the saved password" /></div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-800">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Webhook</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">In the Pathao merchant panel, use this callback URL and secret.</p>
                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-admin.copy-field label="Callback URL" :value="url('/api/webhooks/pathao')" />
                                <x-admin.copy-field label="Webhook secret" :value="$canEdit ? config('services.pathao.webhook_secret') : null" secret
                                                    :empty="$canEdit ? 'Not set: generate one below' : 'Hidden'" />
                                <div class="md:col-span-2">
                                    <x-admin.input name="courier_pathao_webhook_secret" type="password" label="Use my own secret" optional autocomplete="new-password" maxlength="255"
                                                   placeholder="At least 16 characters" :hint="$courierSources['courier_pathao_webhook_secret'] === 'env' ? 'Empty here, so the secret from .env is used.' : 'Leave blank to keep the current secret.'" />
                                    <div class="mt-2"><x-admin.checkbox name="generate_courier_pathao_webhook_secret" label="Generate a new random secret" /></div>
                                </div>
                            </div>
                        </div>
                    </x-admin.card>
                </div>

                <div class="space-y-6 self-start">
                    <x-admin.card title="Status">
                        <dl class="space-y-3 text-sm">
                            @foreach($courierStatusRows as $label => $ready)
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                    <dd><x-admin.status-badge :tone="$ready ? 'green' : 'gray'" :label="$ready ? 'Ready' : 'Not set'" /></dd>
                                </div>
                            @endforeach
                        </dl>
                        <p class="mt-4 border-t border-gray-200 pt-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            Save first, then use "Check connection". Values saved here replace the ones in .env; leave a field empty to use .env.
                        </p>
                    </x-admin.card>
                    <x-admin.alert tone="info" title="Keep status in sync">
                        Webhooks update orders instantly. As a fallback, the scheduler polls parcels every 30 minutes; the server needs a cron entry running <code>php artisan schedule:run</code> every minute.
                    </x-admin.alert>
                </div>
            </div>
        </x-admin.tab-panel>
        {{-- Fraud protection --}}
        <x-admin.tab-panel name="fraud">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-admin.card title="Duplicate & rapid orders" description="Checked instantly at checkout.">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <x-admin.input name="fraud_duplicate_minutes" type="number" label="Duplicate window" min="0" max="1440" suffix="min"
                                           :value="\App\Services\Fraud\FraudSettings::duplicateMinutes()"
                                           hint="Same phone + same products within this time is refused. 0 turns it off." />
                            <x-admin.input name="fraud_ip_max_per_hour" type="number" label="Orders per IP per hour" min="0" max="1000"
                                           :value="\App\Services\Fraud\FraudSettings::ipMaxPerHour()" hint="0 turns it off." />
                            <x-admin.select name="fraud_ip_action" label="Above the IP limit" :options="['flag' => 'Place and flag the order', 'block' => 'Refuse the order']"
                                            :value="\App\Services\Fraud\FraudSettings::ipAction()" hint="Mobile networks share IPs, so flagging is safer." />
                        </div>
                    </x-admin.card>

                    <x-admin.card title="Delivery-history check" description="Runs in the background after each order, so checkout stays fast. Results are cached for 24 hours per number.">
                        <div class="space-y-5">
                            <x-admin.toggle name="fraud_check_enabled" label="Check every new order" description="Saves Low / Medium / High / New risk on the order."
                                            :checked="\App\Services\Fraud\FraudSettings::checkEnabled()" />
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-admin.select name="fraud_driver" label="Source" :options="\App\Services\Fraud\FraudSettings::DRIVERS"
                                                :value="\App\Services\Fraud\FraudSettings::driver()"
                                                hint="Steadfast uses the keys from the Courier tab." />
                                <x-admin.input name="fraud_high_cancel_count" type="number" label="High risk after" min="1" max="100" suffix="cancellations"
                                               :value="\App\Services\Fraud\FraudSettings::highCancelCount()" hint="Also High when under 50% of parcels are delivered." />
                                <div class="md:col-span-2">
                                    <x-admin.input name="fraud_bdcourier_api_key" type="password" label="BD Courier API key" optional autocomplete="new-password" maxlength="500" prefix="bi-key"
                                                   :placeholder="$fraudKeySaved ? 'Saved (hidden). Paste a new key to replace it.' : ''"
                                                   :hint="$fraudKeySaved ? 'Stored encrypted. Leave blank to keep the saved key.' : ($fraudKeyFromEnv ? 'Empty here, so the key from .env is used.' : 'Only needed for the BD Courier source. Stored encrypted.')" />
                                    @if($fraudKeySaved)
                                        <div class="mt-2"><x-admin.checkbox name="remove_fraud_bdcourier_api_key" label="Remove the saved key" /></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-admin.card>

                    <x-admin.card title="High-risk cash-on-delivery orders" description="Online-paid orders are never held.">
                        <div class="space-y-5">
                            <x-admin.toggle name="fraud_hold_high_risk" label="Put on hold" description="Status becomes On Hold (Risky) until an admin marks it safe, confirms or cancels it. Customers still see “Pending”."
                                            :checked="\App\Services\Fraud\FraudSettings::holdHighRisk()" />
                            <div x-data="{ on: @js((bool) old('fraud_advance_enabled', \App\Services\Fraud\FraudSettings::advanceEnabled())), type: @js(old('fraud_advance_type', \App\Services\Fraud\FraudSettings::advanceType())) }"
                                 @click="$nextTick(() => on = $el.querySelector('[name=fraud_advance_enabled]').value === '1')" class="space-y-4">
                                <x-admin.toggle name="fraud_advance_enabled" label="Ask for an advance payment" description="Shown on the customer's order page and on the admin order page."
                                                :checked="\App\Services\Fraud\FraudSettings::advanceEnabled()" />
                                <div x-show="on" x-cloak class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <x-admin.select name="fraud_advance_type" label="Advance amount" x-model="type"
                                                    :options="['delivery_charge' => 'The order\'s delivery charge', 'fixed' => 'A fixed amount']"
                                                    :value="\App\Services\Fraud\FraudSettings::advanceType()" />
                                    <div x-show="type === 'fixed'">
                                        <x-admin.input name="fraud_advance_amount" type="number" label="Fixed amount" prefix="Tk" step="1" min="0"
                                                       :value="\App\Services\Fraud\FraudSettings::advanceAmount() ?: ''" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-admin.card>
                </div>

                <x-admin.card title="Risk levels" class="self-start">
                    <ul class="space-y-3 text-sm">
                        @foreach(\App\Enums\RiskLevel::cases() as $level)
                            <li class="flex items-start gap-3">
                                <x-admin.status-badge :tone="$level->tone()" :dot="false" :label="$level->label()" class="shrink-0" />
                                <span class="text-gray-600 dark:text-gray-300">{{ $level->description() }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 border-t border-gray-200 pt-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        A failed check never blocks an order: it is marked “Risk unknown” and admins get a notification.
                        <a href="{{ route('admin.blacklist.index') }}" class="font-medium text-brand-600 hover:underline dark:text-brand-400">Manage the phone blacklist</a>.
                    </p>
                </x-admin.card>
            </div>
        </x-admin.tab-panel>
    </fieldset>

    @if($canEdit)
        {{-- One save button for every tab, always in reach. --}}
        <div class="sticky bottom-0 z-20 -mx-4 mt-6 flex items-center justify-end gap-4 border-t border-gray-200 bg-white/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 dark:border-gray-800 dark:bg-gray-950/90">
            <p x-show="dirty" x-cloak class="mr-auto flex items-center gap-2 text-sm text-amber-700 dark:text-amber-400">
                <i class="bi bi-circle-fill text-[8px]" aria-hidden="true"></i> You have unsaved changes
            </p>
            <x-admin.button type="submit" icon="bi-check-lg">Save settings</x-admin.button>
        </div>
    @endif
</form>

@if($canEdit)
    <form id="flash-end-form" method="POST" action="{{ route('marketing.flashDeal.end') }}" class="hidden"
          data-confirm="Flash prices switch back to regular prices and every product leaves the campaign." data-confirm-title="End the flash deal now?" data-confirm-button="End campaign">@csrf</form>
@endif

@if($canEdit)
    <form id="fb-test-form" method="POST" action="{{ route('admin.settings.facebook-test') }}" class="hidden">@csrf</form>
    <form id="courier-test-steadfast" method="POST" action="{{ route('admin.settings.courier-test', 'steadfast') }}" class="hidden">@csrf</form>
    <form id="courier-test-pathao" method="POST" action="{{ route('admin.settings.courier-test', 'pathao') }}" class="hidden">@csrf</form>
@endif
@endsection
