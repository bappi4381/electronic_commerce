<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Setting;
use App\Services\Courier\CourierSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourierSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.steadfast.api_key' => null,
            'services.steadfast.secret_key' => null,
            'services.steadfast.webhook_secret' => null,
            'services.pathao.base_url' => 'https://api-hermes.pathao.com',
        ]);
    }

    public function test_keys_saved_in_admin_are_encrypted_and_used_for_api_calls(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.settings.update'), $this->payload([
                'courier_steadfast_api_key' => 'sf-api-key-123',
                'courier_steadfast_secret_key' => 'sf-secret-456',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('sf-api-key-123', Setting::get('courier_steadfast_api_key'));
        $this->assertNotSame('sf-secret-456', Setting::get('courier_steadfast_secret_key'), 'The secret key must not be stored in plain text.');
        $this->assertSame('sf-secret-456', Crypt::decryptString(Setting::get('courier_steadfast_secret_key')));

        CourierSettings::apply();
        $this->assertSame('admin', CourierSettings::source('courier_steadfast_secret_key'));

        Http::fake(['portal.packzy.com/*' => Http::response(['status' => 200, 'current_balance' => 500])]);
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.settings.courier-test', 'steadfast'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Tk 500.00'));

        Http::assertSent(fn (HttpRequest $request) => $request->hasHeader('Api-Key', 'sf-api-key-123') && $request->hasHeader('Secret-Key', 'sf-secret-456'));
    }

    public function test_blank_secret_keeps_the_saved_one_and_remove_falls_back_to_env(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->post(route('admin.settings.update'), $this->payload(['courier_steadfast_secret_key' => 'first-secret']));
        $this->actingAs($admin, 'admin')->post(route('admin.settings.update'), $this->payload(['courier_steadfast_secret_key' => '']));
        CourierSettings::apply();
        $this->assertSame('first-secret', config('services.steadfast.secret_key'));

        $this->actingAs($admin, 'admin')->post(route('admin.settings.update'), $this->payload(['remove_courier_steadfast_secret_key' => '1']));
        $this->assertNull(Setting::get('courier_steadfast_secret_key'));

        config(['services.steadfast.secret_key' => 'env-secret']);
        CourierSettings::apply();
        $this->assertSame('env-secret', config('services.steadfast.secret_key'));
        $this->assertSame('env', CourierSettings::source('courier_steadfast_secret_key'));
    }

    public function test_generated_webhook_token_is_accepted_by_the_webhook(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.settings.update'), $this->payload(['generate_courier_steadfast_webhook_secret' => '1']))
            ->assertSessionHasNoErrors();

        CourierSettings::apply();
        $token = config('services.steadfast.webhook_secret');
        $this->assertSame(48, strlen($token));

        $this->postJson('/webhooks/steadfast', ['notification_type' => 'tracking_update', 'consignment_id' => 1], ['Authorization' => "Bearer {$token}"])
            ->assertOk();
        $this->postJson('/webhooks/steadfast', ['notification_type' => 'tracking_update', 'consignment_id' => 1], ['Authorization' => 'Bearer wrong-token-000000'])
            ->assertUnauthorized();
    }

    public function test_pathao_environment_switches_the_api_url(): void
    {
        $this->actingAs($this->admin(), 'admin')->post(route('admin.settings.update'), $this->payload(['courier_pathao_mode' => 'sandbox']));
        CourierSettings::apply();

        $this->assertSame('https://courier-api-sandbox.pathao.com', config('services.pathao.base_url'));
        $this->assertSame('sandbox', CourierSettings::pathaoMode());
    }

    public function test_settings_page_shows_the_courier_tab_without_leaking_the_secret_key(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->post(route('admin.settings.update'), $this->payload(['courier_steadfast_secret_key' => 'never-show-this-secret']));
        CourierSettings::apply();

        $this->actingAs($admin, 'admin')->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Check connection')
            ->assertSee(route('webhooks.steadfast'))
            ->assertDontSee('never-show-this-secret');
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.settings.update'), $this->payload([
                'courier_steadfast_api_key' => 'has spaces in it',
                'courier_steadfast_webhook_secret' => 'short',
                'courier_pathao_store_id' => 'abc',
            ]))
            ->assertSessionHasErrors(['courier_steadfast_api_key', 'courier_steadfast_webhook_secret', 'courier_pathao_store_id']);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'shipping_default_charge' => 110,
            'payment_cod_enabled' => 1,
            'payment_online_enabled' => 1,
            'fb_pixel_enabled' => 1,
            'fb_capi_enabled' => 1,
        ];
    }

    private function admin(): Admin
    {
        Role::findOrCreate('Super Admin', 'admin');
        $admin = Admin::firstOrCreate(['email' => 'owner@example.com'], ['name' => 'Owner', 'password' => 'secret123']);
        $admin->assignRole('Super Admin');

        return $admin;
    }
}
