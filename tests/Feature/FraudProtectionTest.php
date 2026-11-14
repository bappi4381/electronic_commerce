<?php

namespace Tests\Feature;

use App\Enums\RiskLevel;
use App\Models\Admin;
use App\Models\Category;
use App\Models\CustomerRiskLog;
use App\Models\Order;
use App\Models\PhoneBlacklist;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Services\Fraud\FraudSettings;
use App\Services\FraudCheckerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FraudProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const BDCOURIER = 'api.bdcourier.com/*';

    private Product $product;

    private Product $otherProduct;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['fraud.drivers.bdcourier.api_key' => 'bd-key', 'fraud.drivers.bdcourier.url' => 'https://api.bdcourier.com/courier-check']);
        Setting::set('shipping_default_charge', '120');

        $category = Category::create(['type' => 'product', 'name' => ['en' => 'Phones', 'bn' => 'ফোন']]);
        $this->product = $this->makeProduct($category, 'Test Phone', 'TP-1');
        $this->otherProduct = $this->makeProduct($category, 'Test Charger', 'TC-1');
        $this->user = User::create(['name' => 'Md Rahim', 'email' => 'rahim@example.com', 'password' => 'secret123', 'phone' => '01712345678']);
        $this->admin();
    }

    // ── Duplicate & rapid orders ───────────────────────────────────

    public function test_same_phone_and_products_within_the_window_is_blocked_with_a_friendly_message(): void
    {
        Log::spy();
        $this->placeOrder()->assertRedirect();

        $this->placeOrder(['phone' => '+880 1712-345678'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already being processed'));

        $this->assertSame(1, Order::count());
        Log::shouldHaveReceived('notice')->withArgs(fn ($message, $context) => $message === 'Checkout blocked: duplicate order'
            && $context['phone'] === '017*****678' && !str_contains(json_encode($context), '01712345678'));
    }

    public function test_other_products_or_an_order_after_the_window_are_allowed(): void
    {
        $this->placeOrder()->assertRedirect();
        $this->placeOrder([], $this->otherProduct)->assertSessionMissing('error');
        $this->assertSame(2, Order::count());

        $this->travel(31)->minutes();
        $this->placeOrder()->assertSessionMissing('error');
        $this->assertSame(3, Order::count());
    }

    public function test_resubmitting_the_same_checkout_returns_the_existing_order(): void
    {
        $this->actingAs($this->user)->postJson('/en/cart/add', ['product_id' => $this->product->id]);
        $this->get('/en/checkout')->assertOk();
        $payload = $this->payload(['checkout_token' => session('checkout_token')]);

        $this->post('/en/place-order', $payload)->assertRedirect();
        $order = Order::sole();

        $this->post('/en/place-order', $payload)
            ->assertRedirect(route('orders.success', ['locale' => 'en', 'order' => $order->id]));
        $this->assertSame(1, Order::count());
    }

    public function test_a_missing_or_foreign_checkout_token_is_refused(): void
    {
        $this->actingAs($this->user)->postJson('/en/cart/add', ['product_id' => $this->product->id]);
        $this->get('/en/checkout');

        $this->post('/en/place-order', $this->payload(['checkout_token' => 'not-the-session-token']))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'expired'));
        $this->assertSame(0, Order::count());
    }

    public function test_too_many_orders_from_one_ip_are_flagged_or_blocked(): void
    {
        Setting::set('fraud_ip_max_per_hour', '2');
        Setting::set('fraud_duplicate_minutes', '0');
        foreach (range(1, 2) as $i) {
            Order::create(['status' => 'delivered', 'payment_status' => 'paid', 'payment_method' => 'cod', 'total_price' => 100, 'delivery_charge' => 0, 'phone' => "0181234567{$i}", 'placed_ip' => '127.0.0.1']);
        }

        $this->placeOrder()->assertSessionMissing('error');
        $this->assertSame(['ip_velocity'], Order::latest('id')->first()->risk_flags);

        Setting::set('fraud_ip_action', 'block');
        $this->placeOrder()->assertSessionHas('error', fn ($m) => str_contains($m, 'Too many orders'));
        $this->assertSame(3, Order::count());
    }

    public function test_blacklisted_and_invalid_numbers_cannot_order(): void
    {
        PhoneBlacklist::create(['phone' => '01712345678', 'reason' => 'Refused three parcels']);

        $this->placeOrder(['phone' => '+8801712345678'])
            ->assertSessionHasErrors(['phone' => __('We can\'t take online orders for this number. Please contact us to complete your order.')]);
        $this->placeOrder(['phone' => '12345'])->assertSessionHasErrors('phone');

        $this->assertSame(0, Order::count());
    }

    // ── Risk check ─────────────────────────────────────────────────

    public function test_risk_levels_follow_the_success_rate_and_cancellations(): void
    {
        $checker = app(FraudCheckerService::class);

        $this->assertSame(RiskLevel::Low, $checker->classify(10, 9, 1, 'test')->level);
        $this->assertSame(RiskLevel::Medium, $checker->classify(10, 7, 2, 'test')->level);
        $this->assertSame(RiskLevel::High, $checker->classify(10, 4, 1, 'test')->level);
        $this->assertSame(RiskLevel::High, $checker->classify(100, 97, 3, 'test')->level, '3 or more cancellations is High.');
        $this->assertSame(RiskLevel::New, $checker->classify(0, 0, 0, 'test')->level);
        $this->assertSame(70.0, $checker->classify(10, 7, 2, 'test')->successRate);
    }

    public function test_high_risk_cod_order_is_held_with_an_advance_and_the_customer_still_sees_pending(): void
    {
        $this->useBdCourier(total: 10, delivered: 3, cancelled: 7);
        Setting::set('fraud_advance_enabled', '1');

        $this->placeOrder()->assertRedirect();

        $order = Order::sole();
        $this->assertSame('on_hold', $order->status);
        $this->assertSame('high', $order->risk_level);
        $this->assertSame('120.00', $order->advance_amount);

        $log = CustomerRiskLog::sole();
        $this->assertSame('01712345678', $log->phone);
        $this->assertSame('30.00', $log->success_rate);
        $this->assertSame('bdcourier', $log->source);
        $this->assertArrayHasKey('Pathao', $log->breakdown());
        $this->assertArrayNotHasKey('reports', $log->raw_response['stats'], 'Only parcel counts are stored.');

        $this->assertStringContainsString('High-risk order', \DB::table('notifications')->pluck('data')->implode(' '));

        $this->actingAs($this->user)->get(route('user.orders.details', $order))
            ->assertOk()
            ->assertSee('Pending')
            ->assertSee('Advance payment needed')
            ->assertDontSee('On Hold')
            ->assertDontSee('High delivery risk');
    }

    public function test_api_failure_marks_the_order_unknown_without_blocking_it(): void
    {
        Setting::set('fraud_driver', 'bdcourier');
        Http::fake([self::BDCOURIER => Http::failedConnection()]);

        $this->placeOrder()->assertRedirect()->assertSessionMissing('error');

        $order = Order::sole();
        $this->assertSame('pending', $order->status);
        $this->assertSame('unknown', $order->risk_level);
        $this->assertStringContainsString('did not answer', CustomerRiskLog::sole()->error());
        $this->assertStringContainsString('Risk check failed', \DB::table('notifications')->pluck('data')->implode(' '));
    }

    public function test_results_are_cached_for_24_hours_and_recheck_bypasses_the_cache(): void
    {
        $this->useBdCourier(total: 20, delivered: 19, cancelled: 1);
        $checker = app(FraudCheckerService::class);

        $this->assertSame(RiskLevel::Low, $checker->check('01712345678')->level);
        $this->assertTrue($checker->check('+8801712345678')->cached);
        Http::assertSentCount(1);

        $this->travel(25)->hours();
        $this->assertFalse($checker->check('01712345678')->cached);
        $checker->check('01712345678', fresh: true);
        Http::assertSentCount(3);
    }

    public function test_local_driver_counts_this_stores_delivered_and_returned_orders(): void
    {
        foreach (['delivered', 'delivered', 'returned'] as $status) {
            Order::create(['status' => $status, 'payment_status' => 'paid', 'payment_method' => 'cod', 'total_price' => 100, 'delivery_charge' => 0, 'phone' => '01712345678']);
        }

        $result = app(FraudCheckerService::class)->check('01712345678');

        $this->assertSame([3, 2, 1], [$result->total, $result->delivered, $result->cancelled]);
        $this->assertSame(RiskLevel::Medium, $result->level);
    }

    // ── Admin ──────────────────────────────────────────────────────

    public function test_admin_can_recheck_mark_safe_and_blacklist(): void
    {
        $this->useBdCourier(total: 10, delivered: 2, cancelled: 8);
        $this->placeOrder();
        $order = Order::sole();
        $admin = Admin::first();
        $this->assertSame('on_hold', $order->status);

        $this->actingAs($admin, 'admin')->get(route('orders.show', $order))
            ->assertOk()->assertSee('Delivery risk')->assertSee('High risk')->assertSee('Pathao')->assertSee('Mark as safe');

        $this->actingAs($admin, 'admin')->post(route('orders.risk.recheck', $order))->assertSessionHas('success');
        $this->assertSame($admin->id, CustomerRiskLog::latest('id')->first()->admin_id);

        $this->actingAs($admin, 'admin')->post(route('orders.risk.safe', $order), ['note' => 'Called, confirmed'])->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('pending', $order->status);
        $this->assertNotNull($order->risk_reviewed_at);
        $this->assertNull($order->advance_amount);

        $this->actingAs($admin, 'admin')->post(route('orders.risk.blacklist', $order), ['reason' => 'Fake orders', 'cancel_order' => '1'])->assertSessionHas('success');
        $this->assertTrue(PhoneBlacklist::isBlocked('01712345678'));
        $this->assertSame('cancelled', $order->fresh()->status);

        $logged = \DB::table('activity_log')->where('log_name', 'fraud')->pluck('properties')->implode(' ');
        $this->assertStringContainsString('017*****678', $logged);
        $this->assertStringNotContainsString('01712345678', $logged);
    }

    public function test_order_list_filters_high_risk_orders(): void
    {
        $high = Order::create(['status' => 'on_hold', 'payment_status' => 'unpaid', 'payment_method' => 'cod', 'total_price' => 100, 'delivery_charge' => 0, 'customer_name' => 'Risky Rakib', 'risk_level' => 'high']);
        Order::create(['status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => 'cod', 'total_price' => 100, 'delivery_charge' => 0, 'customer_name' => 'Safe Sumon', 'risk_level' => 'low']);

        $this->actingAs(Admin::first(), 'admin')->get(route('orders.index', ['risk' => 'high']))
            ->assertOk()->assertSee($high->order_id)->assertSee('High risk')->assertDontSee('Safe Sumon');

        $this->actingAs(Admin::first(), 'admin')->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Risky orders on hold');
    }

    public function test_admin_can_manage_the_blacklist_and_change_limits_in_settings(): void
    {
        $admin = Admin::first();

        $this->actingAs($admin, 'admin')->post(route('admin.blacklist.store'), ['phone' => '+880 1812-345678', 'reason' => 'Prank orders'])->assertSessionHas('success');
        $this->assertTrue(PhoneBlacklist::isBlocked('01812345678'));
        $this->actingAs($admin, 'admin')->get(route('admin.blacklist.index'))->assertOk()->assertSee('01812345678')->assertSee('Prank orders');

        $this->actingAs($admin, 'admin')->post(route('admin.settings.update'), [
            'shipping_default_charge' => 110, 'payment_cod_enabled' => 1, 'payment_online_enabled' => 1, 'fb_pixel_enabled' => 1, 'fb_capi_enabled' => 1,
            'fraud_duplicate_minutes' => 45, 'fraud_ip_max_per_hour' => 8, 'fraud_ip_action' => 'block', 'fraud_check_enabled' => 1,
            'fraud_driver' => 'steadfast', 'fraud_high_cancel_count' => 4, 'fraud_hold_high_risk' => 0,
            'fraud_advance_enabled' => 1, 'fraud_advance_type' => 'fixed', 'fraud_advance_amount' => 200,
        ])->assertSessionHasNoErrors();

        $this->assertSame(45, FraudSettings::duplicateMinutes());
        $this->assertSame(8, FraudSettings::ipMaxPerHour());
        $this->assertSame('block', FraudSettings::ipAction());
        $this->assertSame('steadfast', FraudSettings::driver());
        $this->assertSame(4, FraudSettings::highCancelCount());
        $this->assertFalse(FraudSettings::holdHighRisk());
        $this->assertSame(200.0, FraudSettings::advanceAmount());

        $this->actingAs($admin, 'admin')->get(route('admin.settings.index'))->assertOk()->assertSee('Fraud protection');
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function placeOrder(array $overrides = [], ?Product $product = null)
    {
        $this->actingAs($this->user)->postJson('/en/cart/add', ['product_id' => ($product ?? $this->product)->id]);
        $this->get('/en/checkout')->assertOk();

        return $this->post('/en/place-order', $this->payload($overrides + ['checkout_token' => session('checkout_token')]));
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Md Rahim', 'email' => 'rahim@example.com', 'phone' => '01712345678',
            'shipping_address' => 'House 1, Road 2', 'city' => 'Other', 'payment_method' => 'cod',
        ];
    }

    private function useBdCourier(int $total, int $delivered, int $cancelled): void
    {
        Setting::set('fraud_driver', 'bdcourier');
        Http::fake([self::BDCOURIER => Http::response([
            'status' => 'success',
            'courierData' => [
                'pathao' => ['name' => 'Pathao', 'total_parcel' => $total, 'success_parcel' => $delivered, 'cancelled_parcel' => $cancelled, 'reports' => ['customer name and address']],
                'steadfast' => ['name' => 'Steadfast', 'total_parcel' => 0, 'success_parcel' => 0, 'cancelled_parcel' => 0],
                'summary' => ['total_parcel' => $total, 'success_parcel' => $delivered, 'cancelled_parcel' => $cancelled],
            ],
            'reports' => ['customer name and address'],
        ])]);
    }

    private function makeProduct(Category $category, string $name, string $sku): Product
    {
        $product = Product::create([
            'category_id' => $category->id,
            'name' => ['en' => $name, 'bn' => $name],
            'description' => ['en' => '', 'bn' => ''],
            'price' => 1000,
            'discount' => 0,
            'discounted_price' => 1000,
        ]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => $sku, 'stock' => 50, 'is_active' => true]);

        return $product;
    }

    private function admin(): Admin
    {
        Role::findOrCreate('Super Admin', 'admin');
        $admin = Admin::firstOrCreate(['email' => 'owner@example.com'], ['name' => 'Owner', 'password' => 'secret123']);
        $admin->assignRole('Super Admin');

        return $admin;
    }
}
