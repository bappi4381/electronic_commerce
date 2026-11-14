<?php

namespace Tests\Feature;

use App\Jobs\SendFacebookEventJob;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Services\FacebookCapiService;
use App\Services\FacebookTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class FacebookTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.facebook.pixel_id' => '123456789012345',
            'services.facebook.capi_access_token' => 'test-token',
            'services.facebook.test_event_code' => 'TEST123',
        ]);
        Queue::fake();
        Mail::fake();

        $category = Category::create(['type' => 'product', 'name' => ['en' => 'Phones', 'bn' => 'ফোন']]);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => ['en' => 'Test Phone', 'bn' => 'টেস্ট ফোন'],
            'description' => ['en' => '', 'bn' => ''],
            'price' => 1000,
            'discount' => 10,
            'discounted_price' => 900,
        ]);
        ProductVariant::create(['product_id' => $this->product->id, 'sku' => 'TP-1', 'stock' => 20, 'is_active' => true]);
    }

    // ── Hashing & API call ─────────────────────────────────────────

    public function test_user_data_is_normalised_and_hashed(): void
    {
        $capi = app(FacebookCapiService::class);

        $this->assertSame('8801712345678', $capi->normalisePhone('017-1234 5678'));
        $this->assertSame('8801712345678', $capi->normalisePhone('+880 1712345678'));
        $this->assertSame('8801712345678', $capi->normalisePhone('1712345678'));

        $data = $capi->prepareUserData([
            'email' => '  Test@Example.COM ',
            'phone' => '01712345678',
            'first_name' => 'Md Rahim',
            'city' => 'Cox\'s Bazar',
            'country' => 'BD',
            'client_ip_address' => '103.1.1.1',
            'fbp' => 'fb.1.1700000000000.123',
        ]);

        $this->assertSame(hash('sha256', 'test@example.com'), $data['em']);
        $this->assertSame(hash('sha256', '8801712345678'), $data['ph']);
        $this->assertSame(hash('sha256', 'mdrahim'), $data['fn']);
        $this->assertSame(hash('sha256', 'coxsbazar'), $data['ct']);
        $this->assertSame(hash('sha256', 'bd'), $data['country']);
        $this->assertSame('103.1.1.1', $data['client_ip_address']);
        $this->assertSame('fb.1.1700000000000.123', $data['fbp']);
        $this->assertSame($data, $capi->prepareUserData($data), 'Already hashed data must not be hashed twice.');
    }

    public function test_send_event_posts_to_the_graph_api_with_the_test_code(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1, 'fbtrace_id' => 'abc'])]);

        app(FacebookCapiService::class)->sendEvent('Purchase', 'purchase_ORD-1', ['email' => 'a@b.com'], ['value' => 100, 'currency' => 'BDT'], 'https://shop.test/x');

        Http::assertSent(function (HttpRequest $request) {
            $event = $request['data'][0];

            return $request->url() === 'https://graph.facebook.com/v19.0/123456789012345/events'
                && $request['access_token'] === 'test-token'
                && $request['test_event_code'] === 'TEST123'
                && $event['event_name'] === 'Purchase'
                && $event['event_id'] === 'purchase_ORD-1'
                && $event['action_source'] === 'website'
                && $event['user_data']['em'] === hash('sha256', 'a@b.com');
        });
    }

    public function test_an_api_error_throws_so_the_job_is_retried(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid token']], 400)]);

        $this->expectException(RuntimeException::class);
        app(FacebookCapiService::class)->sendEvent('PageView', 'pv_1', []);
    }

    // ── Store events ───────────────────────────────────────────────

    public function test_product_page_sends_view_content_with_the_same_event_id_as_the_pixel(): void
    {
        $html = $this->get("/en/product/{$this->product->id}")->assertOk()->getContent();

        $this->assertStringContainsString("fbq('init'", $html);
        $this->assertStringContainsString("'PageView'", $html);

        $job = $this->pushed('ViewContent');
        $this->assertStringContainsString($job->eventId, $html);
        $this->assertSame([(string) $this->product->id], $job->customData['content_ids']);
        $this->assertSame('BDT', $job->customData['currency']);
    }

    public function test_add_to_cart_returns_the_browser_event_matching_the_server_event(): void
    {
        $response = $this->postJson('/en/cart/add', ['product_id' => $this->product->id, 'quantity' => 2])->assertOk();

        $job = $this->pushed('AddToCart');
        $this->assertSame($job->eventId, $response->json('fb_event.id'));
        $this->assertSame('AddToCart', $response->json('fb_event.name'));
        $this->assertEquals(1800, $job->customData['value']);
    }

    public function test_cod_order_sends_initiate_checkout_once_and_purchase_once(): void
    {
        $user = User::create(['name' => 'Md Rahim', 'email' => 'rahim@example.com', 'password' => 'secret123', 'phone' => '01712345678']);
        $this->actingAs($user);
        $this->postJson('/en/cart/add', ['product_id' => $this->product->id]);

        $this->get('/en/checkout')->assertOk();
        $this->get('/en/checkout')->assertOk();
        $this->assertCount(1, $this->pushedAll('InitiateCheckout'), 'Refreshing checkout must not repeat InitiateCheckout.');

        $this->post('/en/place-order', [
            'name' => 'Md Rahim', 'email' => 'rahim@example.com', 'phone' => '01712345678',
            'shipping_address' => 'House 1, Road 2', 'city' => 'Other', 'payment_method' => 'cod',
            'checkout_token' => session('checkout_token'),
        ])->assertRedirect();

        $order = Order::latest('id')->firstOrFail();
        $purchase = $this->pushed('Purchase');
        $this->assertSame('purchase_' . $order->order_id, $purchase->eventId);
        $this->assertSame($order->order_id, $purchase->customData['order_id']);
        $this->assertSame(1, $purchase->customData['num_items']);
        $this->assertSame(hash('sha256', '8801712345678'), $purchase->userData['ph']);

        app(FacebookTracking::class)->purchase($order->fresh());
        $this->assertCount(1, $this->pushedAll('Purchase'), 'Purchase must reach the Conversions API only once per order.');

        $this->get("/en/orders/success/{$order->id}")->assertOk()->assertSee('purchase_' . $order->order_id, false);
        $this->get("/en/orders/success/{$order->id}")->assertOk()->assertDontSee('purchase_' . $order->order_id, false);
    }

    public function test_fbclid_is_stored_as_an_unencrypted_fbc_cookie(): void
    {
        $response = $this->get('/en/products?fbclid=IwAR0abc_DEF-123');

        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === '_fbc');
        $this->assertNotNull($cookie);
        $this->assertMatchesRegularExpression('/^fb\.1\.\d{13}\.IwAR0abc_DEF-123$/', $cookie->getValue());
    }

    public function test_admin_switches_turn_tracking_off(): void
    {
        Setting::set('fb_pixel_enabled', '0');
        Setting::set('fb_capi_enabled', '0');

        $html = $this->get("/en/product/{$this->product->id}")->assertOk()->getContent();

        $this->assertStringNotContainsString("fbq('init'", $html);
        Queue::assertNotPushed(SendFacebookEventJob::class);
    }

    // ── Credentials from Admin › Settings › Tracking ───────────────

    public function test_admin_credentials_override_env_and_the_token_is_encrypted(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')->post('/admin/settings', $this->settingsPayload([
            'fb_pixel_id' => '999888777666555',
            'fb_capi_access_token' => 'EAAB-admin-token-1234567890',
            'fb_test_event_code' => 'TEST999',
        ]))->assertSessionHasNoErrors();

        $stored = Setting::query()->where('key', 'fb_capi_access_token')->value('value');
        $this->assertNotSame('EAAB-admin-token-1234567890', $stored, 'The token must not be stored in plain text.');

        $capi = app(FacebookCapiService::class);
        $this->assertSame('999888777666555', $capi->pixelId());
        $this->assertSame('EAAB-admin-token-1234567890', $capi->accessToken());
        $this->assertSame('admin', $capi->source('fb_capi_access_token'));

        $capi->sendEvent('PageView', 'pv_1', []);
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), '/999888777666555/events')
            && $request['access_token'] === 'EAAB-admin-token-1234567890'
            && $request['test_event_code'] === 'TEST999');

        $page = $this->actingAs($admin, 'admin')->get('/admin/settings')->assertOk();
        $page->assertDontSee('EAAB-admin-token-1234567890');
    }

    public function test_blank_token_keeps_the_saved_one_and_the_checkbox_removes_it(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin, 'admin')->post('/admin/settings', $this->settingsPayload(['fb_capi_access_token' => 'EAAB-first-token-1234567890']));

        $this->actingAs($admin, 'admin')->post('/admin/settings', $this->settingsPayload(['fb_capi_access_token' => '']));
        $this->assertSame('EAAB-first-token-1234567890', app(FacebookCapiService::class)->accessToken());

        $this->actingAs($admin, 'admin')->post('/admin/settings', $this->settingsPayload(['remove_fb_capi_access_token' => '1']));
        $this->assertSame('test-token', app(FacebookCapiService::class)->accessToken(), 'Without a saved token the .env value is used.');
    }

    public function test_invalid_pixel_id_is_rejected(): void
    {
        $this->actingAs($this->superAdmin(), 'admin')
            ->post('/admin/settings', $this->settingsPayload(['fb_pixel_id' => 'abc']))
            ->assertSessionHasErrors('fb_pixel_id');
    }

    public function test_send_test_event_button_reports_metas_answer(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1, 'fbtrace_id' => 'XYZ'])]);

        $this->actingAs($this->superAdmin(), 'admin')
            ->post('/admin/settings/facebook-test')
            ->assertRedirect()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Meta received 1 test event') && str_contains($message, 'XYZ'));

        Http::assertSent(fn (HttpRequest $request) => $request['test_event_code'] === 'TEST123');
    }

    private function superAdmin(): \App\Models\Admin
    {
        \Spatie\Permission\Models\Role::findOrCreate('Super Admin', 'admin');
        $admin = \App\Models\Admin::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret123']);
        $admin->assignRole('Super Admin');

        return $admin;
    }

    private function settingsPayload(array $overrides = []): array
    {
        return $overrides + [
            'shipping_default_charge' => 110,
            'payment_cod_enabled' => 1,
            'payment_online_enabled' => 1,
            'fb_pixel_enabled' => 1,
            'fb_capi_enabled' => 1,
        ];
    }

    private function pushed(string $event): SendFacebookEventJob
    {
        $jobs = $this->pushedAll($event);
        $this->assertNotEmpty($jobs, "No {$event} job was queued.");

        return $jobs->last();
    }

    private function pushedAll(string $event)
    {
        return Queue::pushed(SendFacebookEventJob::class, fn (SendFacebookEventJob $job) => $job->eventName === $event)->values();
    }
}
