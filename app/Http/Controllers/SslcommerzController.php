<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Order; // Your Order model
use App\Models\Payment;

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

        $post_data = [
            'store_id' => env('SSLC_STORE_ID'),
            'store_passwd' => env('SSLC_STORE_PASSWORD'),
            'total_amount' => $order->total_price,
            'currency' => 'BDT',
            'tran_id' => $order->order_id,
            'success_url' => route(env('SSLC_ROUTE_SUCCESS')),
            'fail_url' => route(env('SSLC_ROUTE_FAILURE')),
            'cancel_url' => route(env('SSLC_ROUTE_CANCEL')),

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
        $tran_id = $request->tran_id;

        $order = Order::where('order_id', $tran_id)->first();

        if (!$order) {
            return "Order not found!";
        }

        // Update order status
        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

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

        return redirect()->route('orders.success', $order->id);
    }

    /**
     * Payment Fail
     */
    public function fail(Request $request)
    {
        $tran_id = $request->tran_id;
        return "Payment Failed! Transaction ID: $tran_id";
    }

    /**
     * Payment Cancel
     */
    public function cancel(Request $request)
    {
        $tran_id = $request->tran_id;
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