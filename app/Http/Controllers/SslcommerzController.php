<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Order; // Your Order model
use App\Models\Payment;
use App\Models\StockMovement;

class SslcommerzController extends Controller
{
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

        $post_data = [
            'store_id' => env('SSLC_STORE_ID'),
            'store_passwd' => env('SSLC_STORE_PASSWORD'),
            'total_amount' => $order->total_price,
            'currency' => 'BDT',
            'tran_id' => $order->order_id,
            'success_url' => route(env('SSLC_ROUTE_SUCCESS'), $routeParams),
            'fail_url' => route(env('SSLC_ROUTE_FAILURE'), $routeParams),
            'cancel_url' => route(env('SSLC_ROUTE_CANCEL'), $routeParams),

            // Customer info
            'cus_name' => $order->customer_name ?? 'Test Customer',
            'cus_email' => $order->customer_email ?? 'customer@test.com',
            'cus_add1' => $order->shipping_address ?? 'Dhaka',
            'cus_city' => $order->city ?? 'Dhaka',
            'cus_phone' => $order->phone ?? '01711111111',
            'cus_country' => $order->country ?? 'Bangladesh',

            // Shipping info (required for Courier)
            'shipping_method' => 'Courier',
            'ship_name'    => $order->customer_name ?? 'Test Customer',
            'ship_add1'    => $order->shipping_address ?? 'Dhaka',
            'ship_city'    => $order->city ?? 'Dhaka',
            'ship_country' => $order->country ?? 'Bangladesh',
            'ship_phone'   => $order->phone ?? '01711111111',
            'ship_postcode'=> $order->postcode ?? '1216', // make sure your Order has postcode

            // Product info
            'product_name' => 'Products',
            'product_category' => 'Ecommerce',
            'product_profile' => 'general',
        ];

        $api_url = env('SSLC_SANDBOX', true)
            ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';

        $response = Http::asForm()->post($api_url, $post_data)->json();

        if (isset($response['status']) && $response['status'] === 'SUCCESS') {
            return redirect($response['GatewayPageURL']);
        }

        return response()->json([
            'status' => $response['status'] ?? 'FAILED',
            'failedreason' => $response['failedreason'] ?? 'Unknown error',
            'response' => $response
        ]);

    }

    /**
     * Payment Success
     */
    public function success(Request $request)
    {
        $tran_id = $request->input('tran_id');

        $order = Order::where('order_id', $tran_id)->first();

        if (!$order) {
            return "Order not found!";
        }

        // Update order status
        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        // Record committed stock movements for each reserved variant
        foreach ($order->orderItems as $item) {
            $variant = $item->variant ?? ($item->product ? $item->product->variants()->first() : null);
            if ($variant) {
                StockMovement::create([
                    'variant_id' => $variant->id,
                    'change' => 0,
                    'type' => 'order_committed',
                    'reason' => 'Payment success committed reservation',
                    'source_type' => Order::class,
                    'source_id' => $order->id,
                    'admin_id' => null,
                ]);
            }
        }

        // Insert into payments table
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'transaction_id' => $request->bank_tran_id ?? $tran_id,
            'payment_method' => 'sslcommerz',
            'amount' => $order->total_price,
            'currency' => 'BDT',
            'status' => 'success',
            'response_data' => json_encode($request->all()),
            'payment_date' => now(),
        ]);

        return redirect()->route('orders.success', ['locale' => app()->getLocale(), 'order' => $order->id]);
    }

    /**
     * Payment Fail
     */
    public function fail(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $order = Order::where('order_id', $tran_id)->first();
        if ($order && $order->status !== 'cancelled') {
            $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
            foreach ($order->orderItems as $item) {
                $variant = $item->variant ?? ($item->product ? $item->product->variants()->first() : null);
                if ($variant) {
                    StockMovement::create([
                        'variant_id' => $variant->id,
                        'change' => intval($item->quantity),
                        'type' => 'order_released',
                        'reason' => 'Payment failure released reservation',
                        'source_type' => Order::class,
                        'source_id' => $order->id,
                        'admin_id' => null,
                    ]);
                    $variant->increment('stock', $item->quantity);
                }
            }
        }

        return "Payment Failed! Transaction ID: $tran_id";
    }

    /**
     * Payment Cancel
     */
    public function cancel(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $order = Order::where('order_id', $tran_id)->first();
        if ($order && $order->status !== 'cancelled') {
            $order->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);
            foreach ($order->orderItems as $item) {
                $variant = $item->variant ?? ($item->product ? $item->product->variants()->first() : null);
                if ($variant) {
                    StockMovement::create([
                        'variant_id' => $variant->id,
                        'change' => intval($item->quantity),
                        'type' => 'order_released',
                        'reason' => 'Payment cancellation released reservation',
                        'source_type' => Order::class,
                        'source_id' => $order->id,
                        'admin_id' => null,
                    ]);
                    $variant->increment('stock', $item->quantity);
                }
            }
        }

        return "Payment Cancelled! Transaction ID: $tran_id";
    }

    /**
     * IPN (Instant Payment Notification)
     */
    public function ipn(Request $request)
    {
        // Validate and update payment status here
        return response()->json(['message' => 'IPN received']);
    }
}