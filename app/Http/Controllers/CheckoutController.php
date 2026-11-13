<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Exceptions\CouponException;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Mail\OrderConfirmation;
use App\Services\AdminNotifier;
use App\Services\CheckoutOptions;
use App\Services\CouponService;
use App\Services\OrderStatusService;
use App\Services\StockService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class CheckoutController extends Controller
{
    public function __construct(
        private StockService $stock,
        private CouponService $coupons,
        private OrderStatusService $orderStatus,
        private AdminNotifier $notifier,
        private CheckoutOptions $checkout,
    ) {
    }

    public function checkoutIndex()
    {
        $cart = session()->get('cart', []);
        $subtotal = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);
        $coupon = $this->coupons->preview(session('coupon_code'), Auth::user(), $subtotal);

        if ($coupon['error']) {
            session()->forget('coupon_code');
            session()->now('error', $coupon['error'] . ' It has been removed from your order.');
        }

        return view('frontend.pages.checkout', [
            'cart' => $cart,
            'discount' => $coupon['discount'],
            'couponCode' => $coupon['coupon']?->code,
            'cityOptions' => $this->checkout->cityOptions(),
            'freeShippingThreshold' => $this->checkout->freeShippingThreshold(),
            'paymentMethods' => $this->checkout->paymentMethods(),
        ]);
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'alt_phone' => 'nullable|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'city' => ['required', 'string', Rule::in(array_keys($this->checkout->cityOptions()))],
            'region' => 'nullable|string|max:100',
            'order_notes' => 'nullable|string|max:1000',
            'payment_method' => ['required', Rule::in($this->checkout->paymentMethods())],
        ], [
            'payment_method.in' => 'That payment method is currently unavailable.',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'Cart is empty!');
        }

        $user = Auth::user();

        // Create order, items, stock reservations and coupon usage atomically
        try {
            $order = DB::transaction(fn () => $this->createOrder($user, $cart, $request, session('coupon_code')));
        } catch (CouponException $e) {
            session()->forget('coupon_code');

            return back()->withInput()->with('error', $e->getMessage() . ' It has been removed — please review your total and confirm again.');
        } catch (InsufficientStockException|ValidationException $e) {
            return back()->withInput()->with('error', $e->getMessage() . ' Please update your cart and try again.');
        }

        $order->load('orderItems');
        $this->notifier->newOrder($order);

        Mail::to($order->customerDisplayEmail() ?? $user->email)->queue(new OrderConfirmation($order, $request->payment_method));

        session()->forget(['cart', 'coupon_code']);

        if ($request->payment_method === 'online') {
            return redirect()->route('sslc.pay', ['locale' => app()->getLocale(), 'orderId' => $order->id]);
        }

        return redirect()->route('orders.success', ['locale' => app()->getLocale(), 'order' => $order->id])
            ->with('success', 'Order placed successfully!');
    }

    private function createOrder($user, array $cart, Request $request, ?string $couponCode): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $request->name,
            'customer_email' => $request->email,
            'phone' => $request->phone,
            'total_price' => 0,
            'delivery_charge' => 0,
            'shipping_address' => $request->shipping_address,
            'city' => $request->city,
            'region' => $request->region,
            'alt_phone' => $request->alt_phone,
            'order_notes' => $request->order_notes,
            'status' => OrderStatus::Pending->value,
            // COD = unpaid until delivered, Online = pending until the gateway confirms
            'payment_status' => $request->payment_method === 'cod' ? PaymentStatus::Unpaid->value : PaymentStatus::Pending->value,
            'payment_method' => $request->payment_method,
        ]);

        $orderItems = [];
        $subtotal = 0.0;

        foreach ($cart as $item) {
            $quantity = max(1, (int) $item['quantity']);
            $variant = $this->resolveVariant($item);

            // Cart items without a variant fall back to the product's default variant; products without
            // any variant are not stock-tracked.
            if ($variant) {
                $this->stock->reserve($variant, $quantity, $order, 'Reserved on checkout placeOrder');
            }

            $lineTotal = $item['price'] * $quantity;
            $subtotal += $lineTotal;

            $orderItems[] = [
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'variant_id' => $variant?->id,
                'quantity' => $quantity,
                'price' => $item['price'],
                'subtotal' => $lineTotal,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        OrderItem::insert($orderItems);

        if ($couponCode) {
            $this->coupons->redeem($couponCode, $user, $order, $subtotal);
        }

        // Charged after the coupon so the free-shipping threshold applies to what the customer pays for items.
        $itemsTotal = max(0, $subtotal - (float) $order->discount_amount);
        $order->delivery_charge = $this->checkout->deliveryCharge($request->city, $itemsTotal);
        $order->total_price = $itemsTotal + $order->delivery_charge;
        $order->save();

        $this->orderStatus->recordInitial($order, null, 'Order placed by customer');

        return $order;
    }

    private function resolveVariant(array $item): ?ProductVariant
    {
        $product = Product::find($item['product_id']);

        if (!$product) {
            throw ValidationException::withMessages(['cart' => 'A product in your cart is no longer available.']);
        }

        if (empty($item['variant_id'])) {
            return $product->variants()->orderBy('id')->first();
        }

        return ProductVariant::where('product_id', $product->id)->find($item['variant_id'])
            ?? throw ValidationException::withMessages(['cart' => 'A product option in your cart is no longer available.']);
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

        $order->load(['user', 'orderItems.product', 'orderItems.variant.attributeValues.attribute', 'orderCourier']);

        $pdf = Pdf::loadView('admin.orders.invoice', compact('order'));

        return $pdf->download('invoice_' . $order->order_id . '.pdf');
    }
}
