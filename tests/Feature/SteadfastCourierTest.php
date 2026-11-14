<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CourierLog;
use App\Models\Order;
use App\Models\OrderCourier;
use App\Services\Courier\SteadfastService;
use App\Support\BdPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SteadfastCourierTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'portal.packzy.com/api/v1';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.steadfast.base_url' => 'https://portal.packzy.com/api/v1',
            'services.steadfast.api_key' => 'test-api-key',
            'services.steadfast.secret_key' => 'test-secret-key',
            'services.steadfast.webhook_secret' => 'hook-token',
            'services.steadfast.webhook_header' => 'Authorization',
        ]);
        Queue::fake();
        Mail::fake();
    }

    public function test_phone_numbers_are_normalised_to_local_mobile_format(): void
    {
        $this->assertSame('01712345678', BdPhone::normalize('+880 1712-345678'));
        $this->assertSame('01712345678', BdPhone::normalize('8801712345678'));
        $this->assertSame('01712345678', BdPhone::normalize('008801712345678'));
        $this->assertSame('01712345678', BdPhone::normalize('1712345678'));
        $this->assertSame('01712345678', BdPhone::normalize('01712345678'));
        $this->assertNull(BdPhone::normalize('0171234567'));
        $this->assertNull(BdPhone::normalize('01212345678'), 'Operator digit must be 3-9.');
        $this->assertNull(BdPhone::normalize(null));
    }

    public function test_sending_an_order_books_the_parcel_logs_the_call_and_ships_the_order(): void
    {
        $this->fakeCreateOrder();
        $order = $this->order(['phone' => '+8801712345678']);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('orders.courier.store', $order), ['courier' => 'steadfast'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'SFR-TRACK-1'));

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://portal.packzy.com/api/v1/create_order'
            && $request->hasHeader('Api-Key', 'test-api-key')
            && $request->hasHeader('Secret-Key', 'test-secret-key')
            && $request['invoice'] === $order->order_id
            && $request['recipient_phone'] === '01712345678'
            && (float) $request['cod_amount'] === 1500.0
            && str_contains($request['recipient_address'], 'House 1, Road 2'));

        $parcel = $order->fresh()->orderCourier;
        $this->assertSame('1001', $parcel->consignment_id);
        $this->assertSame('SFR-TRACK-1', $parcel->tracking_code);
        $this->assertSame('in_review', $parcel->status);
        $this->assertNotNull($parcel->sent_at);
        $this->assertSame('shipped', $order->fresh()->status);

        $log = CourierLog::where('order_id', $order->id)->where('action', 'create_parcel')->firstOrFail();
        $this->assertSame(200, $log->status_code);
        $this->assertSame($order->order_id, $log->request_payload['invoice']);
        $this->assertSame(1001, $log->response_payload['consignment']['consignment_id']);
        $this->assertStringNotContainsString('test-secret-key', json_encode($log->toArray()));
    }

    public function test_an_order_is_never_sent_twice(): void
    {
        $this->fakeCreateOrder();
        $order = $this->order();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('orders.courier.store', $order), ['courier' => 'steadfast']);
        $this->actingAs($admin, 'admin')
            ->post(route('orders.courier.store', $order), ['courier' => 'steadfast'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Already sent'));

        Http::assertSentCount(1);
        $this->assertSame(1, OrderCourier::where('order_id', $order->id)->count());
    }

    public function test_missing_api_keys_do_not_mark_the_order_failed(): void
    {
        Http::fake();
        config(['services.steadfast.api_key' => null]);
        $order = $this->order();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('orders.courier.store', $order), ['courier' => 'steadfast'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Steadfast is not connected'));

        Http::assertNothingSent();
        $this->assertNull($order->fresh()->orderCourier);
    }

    public function test_invalid_phone_is_rejected_before_calling_steadfast(): void
    {
        Http::fake();
        $order = $this->order(['phone' => '12345']);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('orders.courier.store', $order), ['courier' => 'steadfast'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'not a valid Bangladeshi mobile number'));

        Http::assertNothingSent();
        $parcel = $order->fresh()->orderCourier;
        $this->assertTrue($parcel->hasFailed());
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_api_error_marks_the_order_failed_and_a_retry_succeeds(): void
    {
        Http::fakeSequence(self::API . '/create_order')
            ->push(['status' => 400, 'message' => 'Validation failed', 'errors' => ['recipient_address' => ['Address is too short.']]], 422)
            ->push($this->createOrderResponse());
        $order = $this->order();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->post(route('orders.courier.store', $order), ['courier' => 'steadfast'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Address is too short.'));

        $parcel = $order->fresh()->orderCourier;
        $this->assertSame('failed', $parcel->status);
        $this->assertNull($parcel->consignment_id);
        $this->assertStringContainsString('Address is too short.', $parcel->last_error);
        $this->assertSame(422, CourierLog::where('order_id', $order->id)->where('action', 'create_parcel')->value('status_code'));
        $this->assertSame('processing', $order->fresh()->status);

        $this->actingAs($admin, 'admin')
            ->post(route('orders.courier.store', $order), ['courier' => 'steadfast'])
            ->assertSessionHas('success');

        $parcel = $order->fresh()->orderCourier;
        $this->assertSame('1001', $parcel->consignment_id);
        $this->assertNull($parcel->last_error);
        $this->assertFalse($parcel->hasFailed());
        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_timeout_is_reported_and_logged(): void
    {
        Http::fake([self::API . '/*' => Http::failedConnection('cURL error 28: Operation timed out')]);
        $order = $this->order();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('orders.courier.store', $order), ['courier' => 'steadfast'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'did not answer within 15 seconds'));

        $this->assertTrue($order->fresh()->orderCourier->hasFailed());
        $log = CourierLog::where('order_id', $order->id)->where('action', 'create_parcel')->firstOrFail();
        $this->assertNull($log->status_code);
        $this->assertStringContainsString('timed out', $log->remarks);
    }

    public function test_bulk_send_books_processing_orders_and_skips_the_rest(): void
    {
        Http::fake([self::API . '/create_order' => fn (HttpRequest $request) => Http::response([
            'status' => 200,
            'consignment' => ['consignment_id' => random_int(1000, 999999), 'invoice' => $request['invoice'], 'tracking_code' => 'T-' . $request['invoice'], 'status' => 'in_review'],
        ])]);
        $first = $this->order();
        $second = $this->order(['phone' => '01812345678']);
        $pending = $this->order(['status' => 'pending']);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('orders.courier.bulk'), ['courier' => 'steadfast', 'order_ids' => [$first->id, $second->id, $pending->id]])
            ->assertSessionHas('success', fn ($m) => str_starts_with($m, '2 orders sent to Steadfast'))
            ->assertSessionHas('bulk_failures', fn ($failures) => isset($failures[$pending->order_id]));

        Http::assertSentCount(2);
        $this->assertSame('shipped', $first->fresh()->status);
        $this->assertSame('shipped', $second->fresh()->status);
        $this->assertNull($pending->fresh()->orderCourier);
    }

    public function test_webhook_requires_the_bearer_token(): void
    {
        $this->postJson('/webhooks/steadfast', ['notification_type' => 'delivery_status', 'consignment_id' => 1, 'status' => 'delivered'])
            ->assertUnauthorized();
        $this->postJson('/webhooks/steadfast', ['notification_type' => 'delivery_status', 'consignment_id' => 1, 'status' => 'delivered'], ['Authorization' => 'Bearer wrong'])
            ->assertUnauthorized();
    }

    public function test_delivered_webhook_updates_parcel_and_order(): void
    {
        $order = $this->shippedOrder();

        $this->postJson('/webhooks/steadfast', [
            'notification_type' => 'delivery_status',
            'consignment_id' => 1001,
            'invoice' => $order->order_id,
            'cod_amount' => 1500,
            'status' => 'Delivered',
            'delivery_charge' => 60,
            'tracking_message' => 'Delivered to customer',
            'updated_at' => '2026-11-13 12:00:00',
        ], ['Authorization' => 'Bearer hook-token'])
            ->assertOk()
            ->assertExactJson(['status' => 'success', 'message' => 'Webhook received successfully.']);

        $parcel = $order->fresh()->orderCourier;
        $this->assertSame('delivered', $parcel->status);
        $this->assertSame('60.00', $parcel->shipping_charge);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertTrue(CourierLog::where('order_id', $order->id)->where('action', 'webhook_delivery_status')->exists());
    }

    public function test_cancelled_webhook_marks_the_order_returned(): void
    {
        $order = $this->shippedOrder();

        $this->postJson('/webhooks/steadfast', ['notification_type' => 'delivery_status', 'consignment_id' => 1001, 'status' => 'cancelled'], ['Authorization' => 'Bearer hook-token'])
            ->assertOk();

        $this->assertSame('cancelled', $order->fresh()->orderCourier->status);
        $this->assertSame('returned', $order->fresh()->status);
    }

    public function test_tracking_update_webhook_is_logged_without_changing_status(): void
    {
        $order = $this->shippedOrder();

        $this->postJson('/webhooks/steadfast', [
            'notification_type' => 'tracking_update',
            'consignment_id' => 1001,
            'invoice' => $order->order_id,
            'tracking_message' => 'Package arrived at the sorting center.',
        ], ['Authorization' => 'Bearer hook-token'])->assertOk();

        $this->assertSame('in_review', $order->fresh()->orderCourier->status);
        $this->assertSame('shipped', $order->fresh()->status);
        $log = CourierLog::where('order_id', $order->id)->where('action', 'webhook_tracking_update')->firstOrFail();
        $this->assertSame('Package arrived at the sorting center.', $log->remarks);
    }

    public function test_sync_command_polls_parcels_that_are_still_moving(): void
    {
        Http::fake([self::API . '/status_by_cid/1001' => Http::response(['status' => 200, 'delivery_status' => 'delivered'])]);
        $moving = $this->shippedOrder();
        $done = $this->shippedOrder('2002', 'delivered');

        $this->artisan('courier:sync-status')->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertSame('delivered', $moving->fresh()->orderCourier->status);
        $this->assertSame('delivered', $moving->fresh()->status);
        $this->assertSame(200, CourierLog::where('order_id', $moving->id)->where('action', 'status_check')->value('status_code'));
        $this->assertSame('delivered', $done->fresh()->orderCourier->status);
    }

    public function test_balance_is_read_and_shown_on_the_dashboard(): void
    {
        Http::fake([self::API . '/get_balance' => Http::response(['status' => 200, 'current_balance' => 2450.5])]);

        $this->assertSame(2450.5, app(SteadfastService::class)->getBalance());

        $this->actingAs($this->admin(), 'admin')->get(route('admin.dashboard', ['refresh_balance' => 1]))
            ->assertOk()
            ->assertSee('Courier balance')
            ->assertSee('Tk 2,450.50');
    }

    public function test_customer_order_page_hides_courier_api_logs(): void
    {
        $order = $this->shippedOrder();
        CourierLog::create(['order_id' => $order->id, 'action' => 'create_parcel', 'request_payload' => ['invoice' => 'x'], 'status_code' => 200]);
        CourierLog::create(['order_id' => $order->id, 'action' => 'status_changed', 'status_from' => 'pending', 'status_to' => 'in_review']);

        $this->assertSame(['status_changed'], $order->courierLogs()->customerVisible()->pluck('action')->all());
    }

    private function fakeCreateOrder(): void
    {
        Http::fake([self::API . '/create_order' => Http::response($this->createOrderResponse())]);
    }

    private function createOrderResponse(): array
    {
        return [
            'status' => 200,
            'message' => 'Consignment has been created successfully.',
            'consignment' => ['consignment_id' => 1001, 'invoice' => 'x', 'tracking_code' => 'SFR-TRACK-1', 'status' => 'in_review'],
        ];
    }

    private function order(array $attributes = []): Order
    {
        return Order::create($attributes + [
            'status' => 'processing',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'total_price' => 1500,
            'delivery_charge' => 60,
            'customer_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'shipping_address' => 'House 1, Road 2, Dhanmondi',
            'city' => 'Dhaka',
        ]);
    }

    private function shippedOrder(string $consignmentId = '1001', string $status = 'in_review'): Order
    {
        $order = $this->order(['status' => $status === 'delivered' ? 'delivered' : 'shipped']);
        OrderCourier::create([
            'order_id' => $order->id,
            'courier_name' => 'steadfast',
            'consignment_id' => $consignmentId,
            'tracking_code' => 'SFR-' . $consignmentId,
            'status' => $status,
            'sent_at' => now()->subDay(),
        ]);

        return $order;
    }

    private function admin(): Admin
    {
        Role::findOrCreate('Super Admin', 'admin');
        $admin = Admin::firstOrCreate(['email' => 'owner@example.com'], ['name' => 'Owner', 'password' => 'secret123']);
        $admin->assignRole('Super Admin');

        return $admin;
    }
}
