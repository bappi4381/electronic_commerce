<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\FlashSale;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FlashCampaignTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        FlashSale::flush();
        $this->category = Category::create(['type' => 'product', 'name' => ['en' => 'Phones', 'bn' => 'ফোন']]);
        Role::findOrCreate('Super Admin', 'admin');
        $this->admin = Admin::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret123']);
        $this->admin->assignRole('Super Admin');
    }

    public function test_flash_discount_applies_only_between_start_and_end_then_prices_return(): void
    {
        $product = $this->product(regularDiscount: 5);
        $this->schedule(startsIn: 2, endsIn: 5);

        $this->actingAs($this->admin, 'admin')->post(route('marketing.updateDiscount', $product), ['discount' => 5, 'is_flash_deal' => 1, 'flash_discount' => 30])
            ->assertSessionHas('success');

        $flashSale = app(FlashSale::class);
        $this->assertSame(FlashSale::STATUS_SCHEDULED, $flashSale->status());
        $this->assertNull($flashSale->campaign(), 'Not visible before the start.');
        $this->assertSame(950.0, $product->fresh()->unitPrice(), 'Regular price before the start.');

        $this->travel(3)->hours();
        $flashSale->sync();
        $this->assertSame(700.0, $product->fresh()->unitPrice(), 'Flash price while running.');
        $this->assertNotNull($flashSale->campaign());

        $this->travel(3)->hours();
        $flashSale->sync();
        $product->refresh();
        $this->assertSame(950.0, $product->unitPrice(), 'Regular price after the end.');
        $this->assertFalse($product->is_flash_deal, 'Auto-end takes the product out of the campaign.');
        $this->assertNull($product->flash_discount);
        $this->assertNull($flashSale->campaign());
    }

    public function test_end_campaign_now_restores_prices_and_removes_campaign_only_discounts(): void
    {
        $withFlash = $this->product(regularDiscount: 10);
        $withFlash->update(['is_flash_deal' => true, 'flash_discount' => 40]);
        $legacy = $this->product(regularDiscount: 25, sku: 'LG-1');
        $legacy->update(['is_flash_deal' => true]);
        app(FlashSale::class)->sync();
        $this->assertSame(600.0, $withFlash->fresh()->unitPrice());

        $this->actingAs($this->admin, 'admin')->post(route('marketing.flashDeal.end'), ['remove_discounts' => 1])
            ->assertSessionHas('success', fn ($m) => str_contains($m, '2 products left the campaign'));

        $this->assertSame(900.0, $withFlash->fresh()->unitPrice(), 'Its own 10% discount is back.');
        $this->assertSame(1000.0, $legacy->fresh()->unitPrice(), 'A discount that existed only for the campaign is removed.');
        $this->assertSame(0, Product::where('is_flash_deal', true)->count());
        $this->assertSame(FlashSale::STATUS_ENDED, app(FlashSale::class)->status());
    }

    public function test_editing_the_regular_discount_during_a_campaign_keeps_the_flash_price(): void
    {
        $product = $this->product(regularDiscount: 0);
        $product->update(['is_flash_deal' => true, 'flash_discount' => 20]);
        app(FlashSale::class)->sync();

        $this->actingAs($this->admin, 'admin')->post(route('marketing.updateDiscount', $product), ['discount' => 15, 'is_flash_deal' => 1, 'flash_discount' => 20]);

        $product->refresh();
        $this->assertSame(800.0, $product->unitPrice(), 'Still the flash price.');
        $this->assertSame(15.0, $product->regularDiscount());

        $this->actingAs($this->admin, 'admin')->post(route('marketing.updateDiscount', $product), ['discount' => 15]);
        $this->assertSame(850.0, $product->fresh()->unitPrice(), 'Leaving the campaign applies the new regular discount.');
    }

    public function test_flagging_after_the_campaign_ended_keeps_the_product_and_its_discount(): void
    {
        $ended = $this->product(regularDiscount: 0, sku: 'OLD-1');
        $ended->update(['is_flash_deal' => true]);
        $this->schedule(startsIn: -5, endsIn: -1);
        app(FlashSale::class)->sync();
        $this->assertFalse($ended->fresh()->is_flash_deal, 'The end closes the campaign once.');

        $product = $this->product(regularDiscount: 15);

        $this->actingAs($this->admin, 'admin')->post(route('marketing.updateDiscount', $product), ['discount' => 15, 'is_flash_deal' => 1])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Enter the flash discount'));

        $this->actingAs($this->admin, 'admin')->post(route('marketing.updateDiscount', $product), ['discount' => 15, 'is_flash_deal' => 1, 'flash_discount' => 30])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'campaign has ended'));
        app(FlashSale::class)->sync();

        $product->refresh();
        $this->assertTrue($product->is_flash_deal, 'Not removed again by the old end time.');
        $this->assertSame(850.0, $product->unitPrice(), 'Regular price kept while the campaign is not running.');

        $this->schedule(startsIn: -1, endsIn: 2);
        app(FlashSale::class)->sync();
        $this->assertSame(700.0, $product->fresh()->unitPrice(), 'New schedule: flash price live.');
    }

    public function test_end_time_must_be_after_start_time_in_both_forms(): void
    {
        $times = ['flash_deal_title' => 'Eid', 'flash_deal_start_time' => '2026-12-10 10:00', 'flash_deal_end_time' => '2026-12-09 10:00'];

        $this->actingAs($this->admin, 'admin')->post(route('marketing.flashDeal'), $times)->assertSessionHasErrors('flash_deal_end_time');
        $this->actingAs($this->admin, 'admin')->post(route('admin.settings.update'), $times + [
            'shipping_default_charge' => 110, 'payment_cod_enabled' => 1, 'payment_online_enabled' => 1, 'fb_pixel_enabled' => 1, 'fb_capi_enabled' => 1,
        ])->assertSessionHasErrors('flash_deal_end_time');

        $this->actingAs($this->admin, 'admin')->get(route('admin.settings.index'))->assertOk()
            ->assertSee('Starts at')->assertSee('Remove products automatically when it ends');
    }

    private function product(float $regularDiscount, string $sku = 'TP-1'): Product
    {
        $product = new Product([
            'category_id' => $this->category->id,
            'name' => ['en' => 'Phone ' . $sku, 'bn' => 'ফোন'],
            'description' => ['en' => '', 'bn' => ''],
            'price' => 1000,
        ]);
        $product->applyDiscount($regularDiscount)->save();
        ProductVariant::create(['product_id' => $product->id, 'sku' => $sku, 'stock' => 10, 'is_active' => true]);

        return $product;
    }

    private function schedule(int $startsIn, int $endsIn): void
    {
        $now = CarbonImmutable::now('Asia/Dhaka');
        Setting::set('flash_deal_title', 'Eid Flash');
        Setting::set('flash_deal_start_time', $now->addHours($startsIn)->format('Y-m-d H:i:s'));
        Setting::set('flash_deal_end_time', $now->addHours($endsIn)->format('Y-m-d H:i:s'));
    }
}
