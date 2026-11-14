<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Jobs\SendFacebookEventJob;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Store events for Meta: queues the browser Pixel call and the matching Conversions API call with the same
 * event_id so Meta deduplicates them. Scoped per request (see AppServiceProvider).
 */
class FacebookTracking
{
    private const SESSION_KEY = 'fb_pixel_events';
    private const PURCHASE_FIRED_KEY = 'fb_purchase_fired';
    private const CHECKOUT_SIGNATURE_KEY = 'fb_checkout_signature';
    private const ORDER_CONTEXT_TTL = 60 * 60 * 24 * 7;

    /** Browser events to fire on the page being rendered. @var list<array{name: string, data: array, id: string}> */
    private array $pending = [];

    public function __construct(private FacebookCapiService $capi)
    {
    }

    public function pixelEnabled(): bool
    {
        return $this->capi->pixelEnabled();
    }

    public function pixelId(): ?string
    {
        return $this->capi->pixelId();
    }

    public function eventId(string $prefix): string
    {
        return $prefix . '_' . Str::uuid();
    }

    // ── Store events ────────────────────────────────────────────────

    /** Product page view. Returns the browser event for the current page. */
    public function viewContent(Product $product, float $price, Request $request): ?array
    {
        $eventId = $this->eventId('vc');
        $data = [
            'content_ids' => [(string) $product->id],
            'content_type' => 'product',
            'content_name' => (string) $product->name,
            'content_category' => $product->category?->name,
            'value' => round($price, 2),
            'currency' => $this->currency(),
        ];

        $this->server('ViewContent', $eventId, array_filter($data), $request);

        return $this->browser('ViewContent', array_filter($data), $eventId);
    }

    /**
     * Item added to the cart. With $nextPage the browser event waits for the page after a redirect
     * (form posts and "Buy now"); otherwise the caller hands it to JavaScript (fetch requests).
     */
    public function addToCart(Product $product, float $unitPrice, int $quantity, Request $request, bool $nextPage = false): ?array
    {
        $eventId = $this->eventId('atc');
        $data = [
            'content_ids' => [(string) $product->id],
            'content_type' => 'product',
            'content_name' => (string) $product->name,
            'contents' => [['id' => (string) $product->id, 'quantity' => $quantity, 'item_price' => round($unitPrice, 2)]],
            'value' => round($unitPrice * $quantity, 2),
            'currency' => $this->currency(),
        ];

        $this->server('AddToCart', $eventId, $data, $request);

        return $this->browser('AddToCart', $data, $eventId, $nextPage);
    }

    /** Checkout opened. Fires once per cart content per session, so refreshing the page does not repeat it. */
    public function initiateCheckout(array $cart, float $value, Request $request): ?array
    {
        $signature = md5(json_encode(collect($cart)->map(fn ($item) => [$item['product_id'], $item['variant_id'] ?? null, (int) $item['quantity']])->values()));
        if ($request->session()->get(self::CHECKOUT_SIGNATURE_KEY) === $signature) {
            return null;
        }
        $request->session()->put(self::CHECKOUT_SIGNATURE_KEY, $signature);

        $eventId = $this->eventId('ic');
        $data = [
            'content_ids' => collect($cart)->pluck('product_id')->map(fn ($id) => (string) $id)->unique()->values()->all(),
            'contents' => collect($cart)->map(fn ($item) => ['id' => (string) $item['product_id'], 'quantity' => (int) $item['quantity'], 'item_price' => round((float) $item['price'], 2)])->values()->all(),
            'content_type' => 'product',
            'num_items' => (int) collect($cart)->sum('quantity'),
            'value' => round($value, 2),
            'currency' => $this->currency(),
        ];

        $this->server('InitiateCheckout', $eventId, $data, $request);

        return $this->browser('InitiateCheckout', $data, $eventId);
    }

    /**
     * Keep the shopper's browser context (IP, user agent, fbp, fbc) with the order, because an online payment is
     * confirmed later by the gateway's server, which has none of it.
     */
    public function rememberOrderContext(Order $order, Request $request): void
    {
        Cache::put($this->contextKey($order), $this->requestContext($request), self::ORDER_CONTEXT_TTL);
    }

    /**
     * Server-side Purchase. Sent once per order: cash on delivery when the order is placed,
     * online payments when the gateway confirms them.
     */
    public function purchase(Order $order): void
    {
        if (!$this->capi->enabled() || !$this->purchaseCounts($order)) {
            return;
        }

        if (!Cache::add('fb_purchase_sent_' . $order->id, now()->toIso8601String(), self::ORDER_CONTEXT_TTL * 4)) {
            return;
        }

        $order->loadMissing('orderItems');
        $userData = array_merge(
            Cache::get($this->contextKey($order), []),
            $this->orderUserData($order),
        );

        SendFacebookEventJob::dispatch(
            'Purchase',
            $this->purchaseEventId($order),
            $this->capi->prepareUserData($userData),
            $this->purchaseData($order),
            route('orders.success', ['locale' => app()->getLocale(), 'order' => $order->id]),
            $order->created_at?->timestamp ?? time(),
        );
    }

    /** Browser Purchase for the order confirmation page: only once per order in this browser session. */
    public function purchaseBrowserEvent(Order $order, Request $request): ?array
    {
        if (!$this->pixelEnabled() || !$this->purchaseCounts($order)) {
            return null;
        }

        $fired = $request->session()->get(self::PURCHASE_FIRED_KEY, []);
        if (in_array($order->id, $fired, true)) {
            return null;
        }
        $request->session()->put(self::PURCHASE_FIRED_KEY, array_slice([...$fired, $order->id], -20));

        $order->loadMissing('orderItems');

        return $this->browser('Purchase', $this->purchaseData($order), $this->purchaseEventId($order));
    }

    public function purchaseEventId(Order $order): string
    {
        return 'purchase_' . $order->order_id;
    }

    // ── Browser side ────────────────────────────────────────────────

    /**
     * Events for the Pixel on this page: flashed by the previous request plus those queued now.
     * Reading them consumes them, so each event is printed once.
     *
     * @return list<array{name: string, data: array, id: string}>
     */
    public function browserEvents(): array
    {
        $flashed = request()->hasSession() ? request()->session()->pull(self::SESSION_KEY, []) : [];
        $events = [...$flashed, ...$this->pending];
        $this->pending = [];

        return $events;
    }

    /** Hashed customer fields for the Pixel's advanced matching. */
    public function advancedMatching(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        return $this->capi->prepareUserData(array_filter([
            'em' => $user->email,
            'ph' => $user->phone,
            'fn' => $this->firstName($user->name),
            'ct' => $this->cityFor($user->city),
            'country' => 'bd',
            'external_id' => (string) $user->id,
        ]));
    }

    private function browser(string $name, array $data, string $eventId, bool $nextPage = false): ?array
    {
        if (!$this->pixelEnabled()) {
            return null;
        }

        $event = ['name' => $name, 'data' => $data, 'id' => $eventId];
        if ($nextPage && request()->hasSession()) {
            request()->session()->push(self::SESSION_KEY, $event);
        } else {
            $this->pending[] = $event;
        }

        return $event;
    }

    // ── Server side ─────────────────────────────────────────────────

    private function server(string $name, string $eventId, array $customData, Request $request): void
    {
        if (!$this->capi->enabled()) {
            return;
        }

        SendFacebookEventJob::dispatch(
            $name,
            $eventId,
            $this->capi->prepareUserData($this->requestContext($request) + $this->customerData()),
            $customData,
            $request->fullUrl(),
            time(),
        );
    }

    /** IP, user agent and the _fbp/_fbc cookies (sent unhashed, as Meta requires). */
    private function requestContext(Request $request): array
    {
        return array_filter([
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $request->cookie('_fbc'),
        ]);
    }

    /** Raw identifiers of the logged-in customer; FacebookCapiService hashes them. */
    private function customerData(): array
    {
        $user = Auth::user();

        return array_filter([
            'em' => $user?->email,
            'ph' => $user?->phone,
            'fn' => $this->firstName($user?->name),
            'ct' => $this->cityFor($user?->city),
            'country' => 'bd',
            'external_id' => $user ? (string) $user->id : null,
        ]);
    }

    private function orderUserData(Order $order): array
    {
        return array_filter([
            'em' => $order->customerDisplayEmail(),
            'ph' => $order->customerDisplayPhone(),
            'fn' => $this->firstName($order->customerDisplayName()),
            'ct' => $this->cityFor($order->city),
            'zp' => $order->postal_code,
            'country' => 'bd',
            'external_id' => $order->user_id ? (string) $order->user_id : null,
        ]);
    }

    private function purchaseData(Order $order): array
    {
        $items = $order->orderItems;

        return [
            'value' => round((float) $order->total_price, 2),
            'currency' => $this->currency(),
            'content_type' => 'product',
            'content_ids' => $items->pluck('product_id')->map(fn ($id) => (string) $id)->unique()->values()->all(),
            'contents' => $items->map(fn ($item) => ['id' => (string) $item->product_id, 'quantity' => (int) $item->quantity, 'item_price' => round((float) $item->price, 2)])->values()->all(),
            'num_items' => (int) $items->sum('quantity'),
            'order_id' => $order->order_id,
        ];
    }

    /** Cash on delivery counts when placed; online orders only once paid. Cancelled orders never count. */
    private function purchaseCounts(Order $order): bool
    {
        if (in_array($order->status, [OrderStatus::Cancelled->value, OrderStatus::Refunded->value], true)) {
            return false;
        }

        return $order->payment_method === 'cod' || (bool) $order->paymentStatusEnum()?->isCollected();
    }

    private function contextKey(Order $order): string
    {
        return 'fb_order_context_' . $order->id;
    }

    private function firstName(?string $name): ?string
    {
        $first = trim(strtok(trim((string) $name), ' ') ?: '');

        return $first !== '' ? $first : null;
    }

    /** Shipping zones include a catch-all "Other", which is not a city. */
    private function cityFor(?string $city): ?string
    {
        return $city && $city !== CheckoutOptions::OTHER_ZONE ? $city : null;
    }

    private function currency(): string
    {
        return config('store.currency.code', 'BDT');
    }
}
