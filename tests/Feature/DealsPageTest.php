<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\FlashSale;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealsPageTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        config(['store.timezone' => 'Asia/Dhaka', 'store.flash_sale_cache_seconds' => 0]);
        FlashSale::flush();
        $this->category = Category::create(['type' => 'product', 'name' => ['en' => 'Phones', 'bn' => 'ফোন']]);
    }

    // ── Campaign states ────────────────────────────────────────────

    public function test_active_campaign_shows_only_flash_products_with_a_countdown(): void
    {
        $endsAt = $this->startCampaign('+2 days', 'Eid Flash Sale');
        $flash = $this->product('Flash Phone', 1000, 30, flash: true);
        $this->product('Regular Deal', 1000, 20);

        $response = $this->get('/en/flash-deals')->assertOk();

        $this->assertSame('Eid Flash Sale', $response->viewData('campaign')['title']);
        $this->assertSame([$flash->id], $response->viewData('flashProducts')->pluck('id')->all());
        $response->assertSee('data-section="flash-sale"', false)
            ->assertSee($endsAt->toIso8601String(), false)
            ->assertSee('role="timer"', false)
            ->assertSee('<title>' . e(Setting::get('site_name', 'ONEMALL')) . ' | Eid Flash Sale – Deals &amp; discounts</title>', false);
    }

    public function test_homepage_and_deals_page_show_the_same_flash_products(): void
    {
        $this->startCampaign('+1 day');
        $this->product('Flash A', 1000, 30, flash: true);
        $this->product('Flash B', 2000, 40, flash: true);
        $this->product('Out of stock flash', 1000, 30, flash: true, stock: 0);

        $home = $this->get('/en')->assertOk()->viewData('flashDealProducts')->pluck('id')->all();
        $deals = $this->get('/en/flash-deals')->assertOk()->viewData('flashProducts')->pluck('id')->all();

        $this->assertCount(2, $deals);
        $this->assertSame($home, $deals);
    }

    public function test_expired_campaign_renders_only_the_discount_list(): void
    {
        $this->startCampaign('-1 minute');
        $flash = $this->product('Flash Phone', 1000, 30, flash: true);

        $response = $this->get('/en/flash-deals')->assertOk();

        $this->assertNull($response->viewData('campaign'));
        $this->assertTrue($response->viewData('flashProducts')->isEmpty());
        $this->assertContains($flash->id, $response->viewData('discounts')->pluck('id')->all());
        $response->assertDontSee('data-section="flash-sale"', false)
            ->assertDontSee('role="timer"', false)
            ->assertSee('Deals &amp; discounts', false);
        $this->get('/en')->assertDontSee('data-section="flash-sale"', false);
    }

    public function test_end_time_is_read_in_the_store_timezone(): void
    {
        // 3 hours ago in Dhaka is still 3 hours ahead if misread as UTC. Auto-end off: the products must stay flagged.
        Setting::set('flash_deal_auto_end', '0');
        Setting::set('flash_deal_end_time', CarbonImmutable::now('Asia/Dhaka')->subHours(3)->format('Y-m-d H:i:s'));
        $this->product('Flash Phone', 1000, 30, flash: true);
        $this->assertNull(app(FlashSale::class)->campaign());

        Setting::set('flash_deal_end_time', CarbonImmutable::now('Asia/Dhaka')->addHours(3)->format('Y-m-d H:i:s'));
        $this->assertNotNull(app(FlashSale::class)->campaign());
    }

    public function test_no_flagged_products_means_no_campaign(): void
    {
        $this->startCampaign('+1 day');
        $this->product('Regular Deal', 1000, 20);

        $response = $this->get('/en/flash-deals')->assertOk();

        $this->assertNull($response->viewData('campaign'));
        $response->assertDontSee('data-section="flash-sale"', false);
    }

    public function test_campaign_without_an_end_time_runs_without_a_countdown(): void
    {
        Setting::set('flash_deal_end_time', null);
        $this->product('Flash Phone', 1000, 30, flash: true);

        $this->get('/en/flash-deals')->assertOk()
            ->assertSee('data-section="flash-sale"', false)
            ->assertDontSee('role="timer"', false);
    }

    public function test_sold_count_ignores_cancelled_and_refunded_orders(): void
    {
        $this->startCampaign('+1 day');
        $flash = $this->product('Flash Phone', 1000, 30, flash: true);
        $this->orderOf($flash, 2, 'delivered');
        $this->orderOf($flash, 1, 'pending');
        $this->orderOf($flash, 5, 'cancelled');
        $this->orderOf($flash, 4, 'refunded');

        $this->assertSame(3, (int) app(FlashSale::class)->products()->first()->sold_quantity);
    }

    // ── All discounts ──────────────────────────────────────────────

    public function test_discount_range_chips_filter_by_rounded_discount(): void
    {
        $ids = $this->discountLadder();

        $this->assertEqualsCanonicalizing([$ids[10], $ids[30], $ids[50], $ids[70]], $this->discountIds('/en/flash-deals'));
        $this->assertEqualsCanonicalizing([$ids[10]], $this->discountIds('/en/flash-deals?off=upto-20'));
        $this->assertEqualsCanonicalizing([$ids[10], $ids[30]], $this->discountIds('/en/flash-deals?off=upto-40'));
        $this->assertEqualsCanonicalizing([$ids[70]], $this->discountIds('/en/flash-deals?off=60-plus'));
        $this->assertEqualsCanonicalizing([$ids[10], $ids[30], $ids[50], $ids[70]], $this->discountIds('/en/flash-deals?off=bogus'));
    }

    public function test_discounts_are_sorted_biggest_first(): void
    {
        $ids = $this->discountLadder();

        $this->assertSame([$ids[70], $ids[50], $ids[30], $ids[10]], $this->discountIds('/en/flash-deals'));
    }

    public function test_category_filter_includes_subcategories(): void
    {
        $child = Category::create(['type' => 'product', 'parent_id' => $this->category->id, 'name' => ['en' => 'Android', 'bn' => 'অ্যান্ড্রয়েড']]);
        $other = Category::create(['type' => 'product', 'name' => ['en' => 'Laptops', 'bn' => 'ল্যাপটপ']]);
        $inParent = $this->product('Parent deal', 1000, 20);
        $inChild = $this->product('Child deal', 1000, 25, category: $child);
        $this->product('Laptop deal', 1000, 30, category: $other);

        $this->assertEqualsCanonicalizing([$inParent->id, $inChild->id], $this->discountIds('/en/flash-deals?category=' . $this->category->id));
    }

    public function test_discounts_are_paginated(): void
    {
        foreach (range(1, 25) as $i) {
            $this->product("Deal {$i}", 1000, 10 + $i);
        }

        $first = $this->get('/en/flash-deals')->assertOk()->viewData('discounts');
        $second = $this->get('/en/flash-deals?page=2')->assertOk()->viewData('discounts');

        $this->assertSame(25, $first->total());
        $this->assertCount(20, $first->items());
        $this->assertCount(5, $second->items());
        $this->assertEmpty(array_intersect($first->pluck('id')->all(), $second->pluck('id')->all()));
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function startCampaign(string $relative, string $title = 'Flash Sale'): CarbonImmutable
    {
        $endsAt = CarbonImmutable::now('Asia/Dhaka')->modify($relative)->startOfSecond();
        Setting::set('flash_deal_title', $title);
        Setting::set('flash_deal_end_time', $endsAt->format('Y-m-d H:i:s'));

        return $endsAt;
    }

    private function product(string $name, float $price, float $discount, bool $flash = false, int $stock = 10, ?Category $category = null): Product
    {
        $product = Product::create([
            'category_id' => ($category ?? $this->category)->id,
            'name' => ['en' => $name, 'bn' => $name],
            'description' => ['en' => '', 'bn' => ''],
            'price' => $price,
            'discount' => $discount,
            'discounted_price' => round($price * (1 - $discount / 100), 2),
            'is_flash_deal' => $flash,
        ]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-' . $product->id, 'stock' => $stock, 'is_active' => true]);

        return $product;
    }

    /** @return array<int, int> discount % => product id, plus one product that is not on sale */
    private function discountLadder(): array
    {
        $this->product('Full price', 1000, 0);

        return collect([30, 10, 70, 50])->mapWithKeys(fn ($pct) => [$pct => $this->product("{$pct}% off", 1000, $pct)->id])->all();
    }

    private function discountIds(string $url): array
    {
        return $this->get($url)->assertOk()->viewData('discounts')->pluck('id')->all();
    }

    private function orderOf(Product $product, int $quantity, string $status): void
    {
        $order = Order::create(['status' => $status, 'payment_status' => 'unpaid', 'payment_method' => 'cod', 'total_price' => 1000 * $quantity, 'delivery_charge' => 0]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => $quantity, 'price' => 1000, 'subtotal' => 1000 * $quantity]);
    }
}
