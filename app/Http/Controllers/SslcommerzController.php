<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderStatusService;

class SslcommerzController extends Controller
{
    public function __construct(private OrderStatusService $orderStatus)
    {
    }

    /**
     * Initiate Payment
     */
    public function pay($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $routeParams = ['locale' => app()->getLocale()];

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.success', $routeParams + ['order' => $order->id]);
        }

        $post_data = [
            'store_id' => config('services.sslcommerz.store_id'),
            'store_passwd' => config('services.sslcommerz.store_password'),
            'total_amount' => $order->total_price,
            'currency' => config('services.sslcommerz.currency'),
            'tran_id' => $order->order_id,
            'success_url' => route('sslc.success', $routeParams),
            'fail_url' => route('sslc.failure', $routeParams),
            'cancel_url' => route('sslc.cancel', $routeParams),
            'ipn_url' => route('sslc.ipn', $routeParams),

            // Customer info
            'cus_name' => $order->customerDisplayName(),
            'cus_email' => $order->customerDisplayEmail() ?? 'no-reply@' . parse_url(config('app.url'), PHP_URL_HOST),
            'cus_add1' => $order->shipping_address ?: 'N/A',
            'cus_city' => $order->city ?: 'N/A',
            'cus_phone' => $order->customerDisplayPhone() ?: 'N/A',
            'cus_country' => 'Bangladesh',

            // Shipping info (required for Courier)
            'shipping_method' => 'Courier',
            'ship_name'    => $order->customerDisplayName(),
            'ship_add1'    => $order->shipping_address ?: 'N/A',
            'ship_city'    => $order->city ?: 'N/A',
            'ship_country' => 'Bangladesh',
            'ship_phone'   => $order->customerDisplayPhone() ?: 'N/A',
            'ship_postcode'=> $order->user?->postal_code ?: '0000',

            // Product info
            'product_name' => 'Products',
            'product_category' => 'Ecommerce',
            'product_profile' => 'general',
        ];

        $response = Http::asForm()->timeout(30)->post($this->gatewayUrl('/gwprocess/v4/api.php'), $post_data)->json();

        if (isset($response['status']) && $response['status'] === 'SUCCESS') {
            return redirect($response['GatewayPageURL']);
        }

        return response()->json([
            'status' => $response['status'] ?? 'FAILED',
            'failedreason' => $response['failedreason'] ?? 'Unknown error',
        ], 502);
    }

    /**
     * Payment Success (customer redirect from the gateway)
     */
    public function success(Request $request)
    {
        $order = $this->confirmPayment($request->input('val_id'), $request->input('tran_id'), 'success');

        if (!$order) {
            return response('Payment could not be verified. If you were charged, it will be confirmed automatically or our team will contact you.', 400);
        }

        return redirect()->route('orders.success', ['locale' => app()->getLocale(), 'order' => $order->id]);
    }

    /**
     * Payment Fail
     */
    public function fail(Request $request)
    {
        $tran_id = (string) $request->input('tran_id');
        $this->releaseReservation($tran_id, 'failed', 'Payment failure released reservation');

        return response('Payment Failed! Transaction ID: ' . e($tran_id));
    }

    /**
     * Payment Cancel
     */
    public function cancel(Request $request)
    {
        $tran_id = (string) $request->input('tran_id');
        $this->releaseReservation($tran_id, 'cancelled', 'Payment cancellation released reservation');

        return response('Payment Cancelled! Transaction ID: ' . e($tran_id));
    }

    /**
     * IPN (Instant Payment Notification), sent server-to-server by SSLCommerz.
     */
    public function ipn(Request $request)
    {
        if ($request->input('status') !== 'VALID') {
            Log::info('SSLCommerz IPN ignored: non-valid status', [
                'tran_id' => $request->input('tran_id'),
                'status' => $request->input('status'),
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'IPN received']);
        }

        $order = $this->confirmPayment($request->input('val_id'), $request->input('tran_id'), 'ipn');

        if (!$order) {
            return response()->json(['message' => 'IPN validation failed'], 400);
        }

        return response()->json(['message' => 'IPN processed']);
    }

    /**
     * Verify a transaction with the SSLCommerz validation API and mark the order paid.
     * Safe to call repeatedly (success redirect and IPN both land here).
     */
    private function confirmPayment(?string $valId, ?string $tranId, string $source): ?Order
    {
        $context = ['tran_id' => $tranId, 'val_id' => $valId, 'source' => $source, 'ip' => request()->ip()];

        if (blank($valId) || blank($tranId)) {
            Log::warning('SSLCommerz callback rejected: missing val_id or tran_id', $context);
            return null;
        }

        $order = Order::where('order_id', $tranId)->first();

        if (!$order) {
            Log::warning('SSLCommerz callback rejected: unknown order', $context);
            return null;
        }

        if ($order->payment_status === 'paid') {
            return $order;
        }

        $validation = $this->validateTransaction($valId, $context);

        if (!$validation || !$this->validationMatchesOrder($validation, $order, $valId)) {
            Log::warning('SSLCommerz callback rejected: validation failed or does not match order', $context + [
                'validated_status' => $validation['status'] ?? null,
                'validated_tran_id' => $validation['tran_id'] ?? null,
                'validated_amount' => $validation['currency_amount'] ?? $validation['amount'] ?? null,
                'validated_currency' => $validation['currency_type'] ?? $validation['currency'] ?? null,
                'order_amount' => $order->total_price,
            ]);
            return null;
        }

        DB::transaction(function () use ($order, $validation, $valId) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($locked->payment_status === 'paid') {
                return;
            }

            $locked->update(['payment_status' => PaymentStatus::Paid->value]);

            if ($locked->status === OrderStatus::Cancelled->value) {
                // Money was captured after the reservation was released; leave the status for manual review.
                Log::warning('SSLCommerz payment validated for a cancelled order; manual review required', [
                    'order_id' => $locked->order_id,
                    'val_id' => $valId,
                ]);
            } elseif ($locked->status === OrderStatus::Pending->value) {
                $this->orderStatus->transition($locked, OrderStatus::Confirmed, null, 'Payment received via SSLCommerz');
            }

            Payment::firstOrCreate(
                ['transaction_id' => filled($validation['bank_tran_id'] ?? null) ? $validation['bank_tran_id'] : $valId],
                [
                    'order_id' => $locked->id,
                    'user_id' => $locked->user_id,
                    'payment_method' => 'sslcommerz',
                    'amount' => $locked->total_price,
                    'currency' => config('services.sslcommerz.currency'),
                    'status' => 'success',
                    'response_data' => json_encode($validation),
                    'payment_date' => now(),
                ]
            );
        });

        return $order->fresh();
    }

    private function validateTransaction(string $valId, array $context): ?array
    {
        try {
            $response = Http::timeout(30)->get($this->gatewayUrl('/validator/api/validationserverAPI.php'), [
                'val_id' => $valId,
                'store_id' => config('services.sslcommerz.store_id'),
                'store_passwd' => config('services.sslcommerz.store_password'),
                'v' => 1,
                'format' => 'json',
            ]);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz validation API unreachable: ' . $e->getMessage(), $context);
            return null;
        }

        if (!$response->ok()) {
            Log::error('SSLCommerz validation API returned HTTP ' . $response->status(), $context);
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    private function validationMatchesOrder(array $data, Order $order, string $valId): bool
    {
        if (!in_array($data['status'] ?? null, ['VALID', 'VALIDATED'], true)) {
            return false;
        }

        // currency_type/currency_amount hold what we requested; amount/currency are the settled values.
        $currency = $data['currency_type'] ?? $data['currency'] ?? null;
        $amount = $data['currency_amount'] ?? $data['amount'] ?? null;

        return ($data['val_id'] ?? null) === $valId
            && ($data['tran_id'] ?? null) === $order->order_id
            && is_numeric($amount)
            && round((float) $amount, 2) === round((float) $order->total_price, 2)
            && strcasecmp((string) $currency, (string) config('services.sslcommerz.currency')) === 0;
    }

    private function releaseReservation(string $tranId, string $paymentStatus, string $reason): void
    {
        if ($tranId === '') {
            return;
        }

        DB::transaction(function () use ($tranId, $paymentStatus, $reason) {
            $order = Order::where('order_id', $tranId)->lockForUpdate()->first();

            // Never undo a verified payment; only an order still awaiting payment is cancelled.
            if (!$order || $order->paymentStatusEnum()?->isCollected() || $order->status !== OrderStatus::Pending->value) {
                return;
            }

            $order->update(['payment_status' => $paymentStatus]);
            $this->orderStatus->transition($order, OrderStatus::Cancelled, null, $reason);
        });
    }

    private function gatewayUrl(string $path): string
    {
        $base = config('services.sslcommerz.sandbox')
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';

        return $base . $path;
    }
}
