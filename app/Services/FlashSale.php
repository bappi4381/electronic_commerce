<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The flash deal campaign: title, start and end time, and the flagged products.
 * The homepage, the Deals page and product pages all read it from here so they never disagree.
 *
 * Prices: a product's "flash_discount" replaces its regular discount only while the campaign runs
 * (sync() swaps it in at the start and back at the end). Products without a flash_discount use
 * their regular discount as the flash discount.
 */
class FlashSale
{
    public const CACHE_KEY = 'store.flash_sale.product_ids';

    public const STATUS_NONE = 'none';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_RUNNING = 'running';
    public const STATUS_ENDED = 'ended';

    /** Relations a product card needs. */
    private const CARD_RELATIONS = ['images', 'brand', 'category.parent', 'variants'];

    /** Orders whose items no longer count as sold. */
    private const UNSOLD_STATUSES = [OrderStatus::Cancelled, OrderStatus::Refunded];

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The running campaign, or null: not started yet, already ended, or no flagged products in stock.
     * Without times the campaign runs until the admin ends it.
     *
     * @return array{title: string, starts_at: ?CarbonImmutable, ends_at: ?CarbonImmutable}|null
     */
    public function campaign(): ?array
    {
        $this->syncIfDue();

        if (!$this->isRunning() || $this->productIds() === []) {
            return null;
        }

        return [
            'title' => (string) (Setting::get('flash_deal_title') ?: __('Flash Sale')),
            'starts_at' => $this->startsAt(),
            'ends_at' => $this->endsAt(),
        ];
    }

    public function status(): string
    {
        return match (true) {
            ($start = $this->startsAt()) && $start->isFuture() => self::STATUS_SCHEDULED,
            ($end = $this->endsAt()) && $end->isPast() => self::STATUS_ENDED,
            $this->flaggedCount() > 0 => self::STATUS_RUNNING,
            default => self::STATUS_NONE,
        };
    }

    public function isRunning(): bool
    {
        $start = $this->startsAt();
        $end = $this->endsAt();

        return (!$start || !$start->isFuture()) && (!$end || $end->isFuture());
    }

    public function flaggedCount(): int
    {
        return Product::where('is_flash_deal', true)->count();
    }

    /**
     * Campaign products in display order, with units sold (cancelled and refunded orders excluded) as "sold_quantity".
     * Empty when no campaign is running.
     */
    public function products(): Collection
    {
        if (!$this->campaign()) {
            return collect();
        }

        $ids = $this->productIds();

        return Product::whereIn('id', $ids)
            ->with(self::CARD_RELATIONS)
            ->withSum(['orderItems as sold_quantity' => fn ($q) => $q->whereHas('order', fn ($order) => $order
                ->whereNull('deleted_at')
                ->whereNotIn('status', array_map(fn (OrderStatus $s) => $s->value, self::UNSOLD_STATUSES)))], 'quantity')
            ->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $ids, true))
            ->values();
    }

    public function startsAt(): ?CarbonImmutable
    {
        return $this->time('flash_deal_start_time');
    }

    /** The admin's end time, read in the store's timezone. Null when unset or unreadable. */
    public function endsAt(): ?CarbonImmutable
    {
        return $this->time('flash_deal_end_time');
    }

    public function autoEnd(): bool
    {
        return Setting::bool('flash_deal_auto_end', true);
    }

    /**
     * Bring prices in line with the schedule (run every minute and after every campaign change):
     * apply flash discounts while running, restore regular discounts otherwise, and when the campaign
     * has ended (with auto-end on) take every product out of it.
     *
     * @return array{applied: int, restored: int, closed: int}
     */
    public function sync(): array
    {
        $result = ['applied' => 0, 'restored' => 0, 'closed' => 0];
        $running = $this->isRunning();

        DB::transaction(function () use ($running, &$result) {
            // Restore products that left the campaign, or every applied product when it is not running.
            Product::whereNotNull('pre_flash_discount')
                ->when($running, fn ($q) => $q->where(fn ($q) => $q->where('is_flash_deal', false)->orWhereNull('flash_discount')))
                ->get()
                ->each(function (Product $product) use (&$result) {
                    $product->applyDiscount((float) $product->pre_flash_discount);
                    $product->pre_flash_discount = null;
                    $product->save();
                    $result['restored']++;
                });

            if ($running) {
                Product::where('is_flash_deal', true)->whereNotNull('flash_discount')->get()
                    ->each(function (Product $product) use (&$result) {
                        if ($product->pre_flash_discount === null) {
                            $product->pre_flash_discount = $product->discount;
                        } elseif ((float) $product->discount === (float) $product->flash_discount) {
                            return;
                        }
                        $product->applyDiscount((float) $product->flash_discount)->save();
                        $result['applied']++;
                    });
            }

            // Once per end time: products flagged after the campaign ended wait for the next schedule instead.
            if (($end = $this->endsAt()) && $end->isPast() && $this->autoEnd() && !$this->closedForCurrentEnd()) {
                $result['closed'] = $this->closeProducts(removeDiscounts: true);
                $this->markClosed();
            }
        });

        self::flush();

        if (array_sum($result)) {
            Log::info('Flash sale synced', $result);
        }

        return $result;
    }

    /** At most once a minute from page loads, so start/end prices switch on time even without a running scheduler. */
    public function syncIfDue(): void
    {
        if (Cache::add('flash-sale:sync-due', true, 60)) {
            try {
                $this->sync();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * End the campaign now: stop the schedule, restore regular prices and take products out of it.
     * $removeDiscounts also clears discounts on products whose own discount was the flash discount.
     */
    public function end(bool $removeDiscounts = true): int
    {
        Setting::set('flash_deal_start_time', null);
        Setting::set('flash_deal_end_time', now(config('store.timezone', 'Asia/Dhaka'))->format('Y-m-d H:i:s'));

        $closed = 0;
        DB::transaction(function () use ($removeDiscounts, &$closed) {
            Product::whereNotNull('pre_flash_discount')->get()->each(function (Product $product) {
                $product->applyDiscount((float) $product->pre_flash_discount);
                $product->pre_flash_discount = null;
                $product->save();
            });
            $closed = $this->closeProducts($removeDiscounts);
        });
        $this->markClosed();

        self::flush();

        return $closed;
    }

    private function closedForCurrentEnd(): bool
    {
        return Setting::get('flash_deal_closed_for') === Setting::get('flash_deal_end_time');
    }

    private function markClosed(): void
    {
        Setting::set('flash_deal_closed_for', Setting::get('flash_deal_end_time'));
    }

    /** Take every flagged product out of the campaign. Returns how many were flagged. */
    private function closeProducts(bool $removeDiscounts): int
    {
        $products = Product::where('is_flash_deal', true)->get();

        foreach ($products as $product) {
            // Without a separate flash discount the product's own discount was the campaign price.
            if ($removeDiscounts && $product->flash_discount === null && $product->pre_flash_discount === null) {
                $product->applyDiscount(0);
            }
            $product->is_flash_deal = false;
            $product->flash_discount = null;
            $product->save();
        }

        return $products->count();
    }

    private function time(string $key): ?CarbonImmutable
    {
        $value = Setting::get($key);

        if (!$value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, config('store.timezone', 'Asia/Dhaka'));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Flagged, discounted, in-stock product ids (newest first), cached briefly.
     *
     * @return list<int>
     */
    private function productIds(): array
    {
        return Cache::remember(self::CACHE_KEY, (int) config('store.flash_sale_cache_seconds', 60), fn () => Product::where('is_flash_deal', true)
            ->whereRaw('COALESCE(flash_discount, discount) > 0')
            ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0))
            ->latest()
            ->take((int) config('store.flash_sale_limit', 12))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all());
    }
}
