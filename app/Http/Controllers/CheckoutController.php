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
use App\Services\CartPricer;
use App\Services\CheckoutOptions;
use App\Services\CouponService;
use App\Services\OrderStatusService;
use App\Services\StockService;
use App\Jobs\CheckOrderRiskJob;
use App\Rules\BangladeshiMobile;
use App\Rules\NotBlacklistedPhone;
use App\Services\Fraud\CheckoutGuard;
use App\Services\Fraud\FraudSettings;
use App\Services\Fraud\OrderBlockedException;
use App\Support\BdPhone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    public function checkoutIndex(CartPricer $pricer)
    {
        $cartChanges = array_merge(session('cart_changes', []), $pricer->refreshSession());
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('cart_changes', $cartChanges)
                ->with('error', 'Your cart is empty. Add something before checking out.');
        }

        $user = Auth::user();
        $subtotal = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);
        $coupon = $this->coupons->preview(session('coupon_code'), $user, $subtotal);

        if ($coupon['error']) {
            session()->forget('coupon_code');
            session()->now('error', $coupon['error'] . ' It has been removed from your order.');
        }

        app(\App\Services\FacebookTracking::class)->initiateCheckout($cart, max(0, $subtotal - (float) $coupon['discount']), request());

        // One token per checkout: a second submit of the same form finds the order it already created.
        if (!session('checkout_token')) {
            session(['checkout_token' => Str::random(40)]);
        }

        return view('frontend.pages.checkout', [
            'checkoutToken' => session('checkout_token'),
            'cartChanges' => $cartChanges,
            'cart' => $cart,
            'subtotal' => (float) $subtotal,
            'discount' => (float) $coupon['discount'],
            'coupon' => $coupon['error'] ? null : $coupon['coupon'],
            'deliveryOptions' => $this->checkout->deliveryOptions(),
            'freeShippingThreshold' => $this->checkout->freeShippingThreshold(),
            'paymentMethods' => $this->checkout->paymentMethods(),
            'prefill' => $this->prefill($user),
            'upsells' => app(\App\Services\CartRecommendations::class)->forCart($cart),
            'policyPages' => \App\Models\Page::footerLinks()->whereIn('slug', ['return-policy', 'privacy', 'terms']),
            'subscribed' => $user?->email && \App\Models\Subscriber::where('email', strtolower($user->email))->where('status', true)->exists(),
        ]);
    }

    /** Saved profile details first, then the customer's last order, so returning customers rarely type anything. */
    private function prefill($user): array
    {
        $lastOrder = $user?->orders()->latest('id')->first();
        [$address, $address2] = array_pad(explode("\n", (string) ($user?->address ?: $lastOrder?->shipping_address), 2), 2, '');

        return [
            'name' => $user?->name ?: $lastOrder?->customer_name,
            'email' => $user?->email ?: $lastOrder?->customer_email,
            'phone' => $user?->phone ?: $lastOrder?->phone,
            'alt_phone' => $lastOrder?->alt_phone,
            'address' => trim($address),
            'address_line2' => trim($address2),
            'city' => $user?->city ?: $lastOrder?->city,
            'region' => $user?->state ?: $lastOrder?->region,
            'postal_code' => $user?->postal_code ?: $lastOrder?->postal_code,
        ];
    }

    public function placeOrder(Request $request, CheckoutGuard $guard, CartPricer $pricer)
    {
        $token = (string) $request->input('checkout_token', '');

        // Double click / resubmit / second tab: the order already exists, show it instead of creating another.
        if ($token !== '' && ($existing = Order::where('checkout_token', $token)->where('user_id', Auth::id())->first())) {
            return redirect()->route('orders.success', ['locale' => app()->getLocale(), 'order' => $existing->id])
                ->with('info', __('This order was already placed.'));
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => ['required', 'string', 'max:20', new BangladeshiMobile(), new NotBlacklistedPhone()],
            'alt_phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{6,20}$/'],
            'shipping_address' => 'required|string|max:180',
            'address_line2' => 'nullable|string|max:70',
            'city' => ['required', 'string', Rule::in(array_keys($this->checkout->cityOptions()))],
            'region' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'order_notes' => 'nullable|string|max:1000',
            'payment_method' => ['required', Rule::in($this->checkout->paymentMethods())],
            'newsletter' => 'nullable|boolean',
            'save_info' => 'nullable|boolean',
        ], [
            'payment_method.in' => 'That payment method is currently unavailable.',
            'alt_phone.regex' => 'Enter a valid phone number, e.g. 01700000000.',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'Cart is empty!');
        }

        if ($token === '' || !hash_equals((string) session('checkout_token'), $token)) {
            return redirect()->route('checkout.index', ['locale' => app()->getLocale()])->withInput()
                ->with('error', __('Your checkout page has expired. Please review your order and place it again.'));
        }

        // Never charge a stale price: if anything changed since the page was shown, show the new total first.
        if ($changes = $pricer->refreshSession()) {
            return redirect()->route('checkout.index', ['locale' => app()->getLocale()])->withInput()
                ->with('cart_changes', $changes)
                ->with('error', __('Some items in your cart changed. Please review the new total and place your order again.'));
        }
        $cart = session('cart', []);

        $user = Auth::user();
        $phone = BdPhone::normalize($request->phone);

        // One checkout per phone at a time, so two quick submits cannot both pass the duplicate check.
        $lock = Cache::lock('checkout:' . hash('sha256', $phone), 30);
        if (!$lock->get()) {
            return back()->withInput()->with('error', __('Your order is already being processed. Please wait a moment.'));
        }

        try {
            $flags = $guard->inspect($phone, $guard->cartHash($cart), $request->ip(), $user->id);

            // Create order, items, stock reservations and coupon usage atomically
            $order = DB::transaction(fn () => $this->createOrder($user, $cart, $request, session('coupon_code'), [
                'phone_normalized' => $phone,
                'cart_hash' => $guard->cartHash($cart),
                'placed_ip' => $request->ip(),
                'checkout_token' => $token,
                'risk_flags' => $flags ?: null,
            ]));
        } catch (OrderBlockedException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (CouponException $e) {
            session()->forget('coupon_code');

            return back()->withInput()->with('error', $e->getMessage() . ' It has been removed — please review your total and confirm again.');
        } catch (InsufficientStockException|ValidationException $e) {
            return back()->withInput()->with('error', $e->getMessage() . ' Please update your cart and try again.');
        } finally {
            $lock->release();
        }

        session()->forget('checkout_token');

        if ($request->boolean('newsletter')) {
            \App\Models\Subscriber::updateOrCreate(['email' => strtolower(trim($request->email))], ['status' => true]);
        }

        if ($request->boolean('save_info')) {
            $user->update([
                'phone' => $request->phone,
                'address' => $order->shipping_address,
                'city' => $request->city,
                'state' => $request->region,
                'postal_code' => $request->postal_code,
            ]);
        }

        $order->load('orderItems');
        $this->notifier->newOrder($order);

        if (FraudSettings::checkEnabled()) {
            CheckOrderRiskJob::dispatch($order->id);
        }

        // Cash on delivery is a purchase now; online orders count once the gateway confirms payment.
        $tracking = app(\App\Services\FacebookTracking::class);
        $tracking->rememberOrderContext($order, $request);
        $tracking->purchase($order);

        Mail::to($order->customerDisplayEmail() ?? $user->email)->queue(new OrderConfirmation($order, $request->payment_method));

        session()->forget(['cart', 'coupon_code']);

        if ($request->payment_method === 'online') {
            return redirect()->route('sslc.pay', ['locale' => app()->getLocale(), 'orderId' => $order->id]);
        }

        return redirect()->route('orders.success', ['locale' => app()->getLocale(), 'order' => $order->id])
            ->with('success', 'Order placed successfully!');
    }

    private function createOrder($user, array $cart, Request $request, ?string $couponCode, array $fraudFields = []): Order
    {
        $order = Order::create($fraudFields + [
            'user_id' => $user->id,
            'customer_name' => $request->name,
            'customer_email' => $request->email,
            'phone' => $request->phone,
            'total_price' => 0,
            'delivery_charge' => 0,
            'shipping_address' => collect([$request->shipping_address, $request->address_line2])->map(fn ($line) => trim((string) $line))->filter()->implode("\n"),
            'city' => $request->city,
            'region' => $request->region,
            'postal_code' => $request->postal_code,
            'alt_phone' => $request->alt_phone,
            'order_notes' => $request->order_notes,
            'marketing_opt_in' => $request->boolean('newsletter'),
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


    public function success(Request $request, Order $order)
    {
        $this->authorizeOrderView($request, $order);

        $order->load(['orderItems.product.images', 'orderItems.variant.attributeValues.attribute', 'statusHistories', 'payments']);

        app(\App\Services\FacebookTracking::class)->purchaseBrowserEvent($order, $request);

        return view('frontend.pages.order_success', [
            'order' => $order,
            'deliveryEta' => $this->checkout->deliveryOptions()[$order->city]['eta'] ?? $this->checkout->defaultEta(),
        ]);
    }

    /** Landing page after the gateway reports a failed, cancelled or not-yet-verified payment. */
    public function paymentResult(Request $request, Order $order, string $result)
    {
        abort_unless(in_array($result, ['failed', 'cancelled', 'pending'], true), 404);
        $this->authorizeOrderView($request, $order);

        if ($order->paymentStatusEnum()?->isCollected()) {
            return redirect()->route('orders.success', ['locale' => app()->getLocale(), 'order' => $order->id]);
        }

        $order->load('orderItems.product.images');

        return view('frontend.pages.payment_result', compact('order', 'result'));
    }

    /** Puts an unpaid order's items back in the cart (current prices and stock) so the customer can try again. */
    public function reorder(Order $order)
    {
        abort_unless(Auth::id() === $order->user_id, 403);

        $cart = session('cart', []);
        $skipped = 0;

        foreach ($order->orderItems()->with(['product.images', 'variant.attributeValues.attribute'])->get() as $item) {
            $product = $item->product;
            $variant = $item->variant_id ? $item->variant : null;
            $stock = $variant ? (int) $variant->stock : (int) $product?->variants()->sum('stock');

            if (!$product || $product->trashed() || ($item->variant_id && !$variant) || $stock <= 0) {
                $skipped++;
                continue;
            }

            $key = $variant ? $product->id . '-' . $variant->id : $product->id;
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => (string) $product->name,
                'variant_name' => $variant?->attributeValues->map(fn ($v) => $v->attribute->name . ': ' . $v->value)->implode(' | ') ?: null,
                'quantity' => min((int) $item->quantity, $stock),
                'price' => $variant ? $variant->setRelation('product', $product)->unitPrice() : $product->unitPrice(),
                'image' => $product->images->first()?->image,
                'stock' => $stock,
            ];
        }

        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with(
            $cart ? 'success' : 'error',
            $skipped ? __(':count item(s) are no longer available and were left out.', ['count' => $skipped]) : __('Your items are back in the cart.')
        );
    }

    /** Order pages show personal details: only the owner, an admin, or a signed link from the payment gateway may see them. */
    private function authorizeOrderView(Request $request, Order $order): void
    {
        $allowed = (Auth::check() && Auth::id() === $order->user_id)
            || Auth::guard('admin')->check()
            || $request->hasValidSignature();

        abort_unless($allowed, 404);
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
