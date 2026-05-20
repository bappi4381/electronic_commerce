<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Setting;
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class CheckoutController extends Controller
{
    public function checkoutIndex()
    {
        $cart = session()->get('cart', []);
        return view('frontend.pages.checkout', compact('cart'));
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'alt_phone' => 'nullable|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'order_notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cod,online',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'Cart is empty!');
        }

        $user = Auth::user();
        $password = null;

        // Subtotal
        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        // Delivery charge
        $dhakaCharge = (float) Setting::get('shipping_charge_dhaka', 80);
        $outsideCharge = (float) Setting::get('shipping_charge_outside', 110);
        $deliveryCharge = str_contains(strtolower($request->city), 'dhaka') ? $dhakaCharge : $outsideCharge;

        // Total price
        $grandTotal = $subtotal + $deliveryCharge;

        // Create Order & Items
        $order = DB::transaction(function () use ($user, $cart, $request, $grandTotal, $deliveryCharge) {

            $order = Order::create([
                'user_id' => $user->id,
                'order_id' => 'ORD-' . now()->format('Ymd') . '-' . rand(1000, 9999),
                'total_price' => $grandTotal,
                'delivery_charge' => $deliveryCharge,
                'shipping_address' => $request->shipping_address,
                'city' => $request->city,
                'region' => $request->region,
                'alt_phone' => $request->alt_phone,
                'order_notes' => $request->order_notes,

                // Fixed values
                'status' => 'pending',  

                // COD = unpaid, Online = pending
                'payment_status' => $request->payment_method === 'cod' ? 'unpaid' : 'paid',

                // Save payment method
                'payment_method' => $request->payment_method,
            ]);

            $orderItems = [];

            foreach ($cart as $productId => $item) {
                $orderItems[] = [
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            OrderItem::insert($orderItems);

            return $order;
        });

        $order->load('orderItems');

        // Send confirmation email
        Mail::to($user->email)->queue(new OrderConfirmation(
            $order, 
            $password, 
            $request->payment_method
        ));

        // Clear cart
        session()->forget('cart');

        // Redirect to payment page
        if ($request->payment_method === 'online') {
            return redirect()->route('sslc.pay', $order->id);
        }

        // COD → success page
        return redirect()->route('orders.success', $order->id)
            ->with('success', 'Order placed successfully!');
    }


    public function success(Order $order)
    {
        return view('frontend.pages.order_success', compact('order'));
    }

    public function downloadInvoice(Order $order)
    {
        // Enforce that only the order owner or admin can download the invoice
        if (Auth::id() !== $order->user_id && !Auth::guard('admin')->check()) {
            abort(403, 'Unauthorized action.');
        }

        $order->load(['user', 'orderItems.product']);

        $pdf = Pdf::loadView('admin.orders.invoice', compact('order'));

        return $pdf->download('invoice_' . $order->id . '.pdf');
    }
}
