<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Queue::fake();
        $this->category = Category::create(['type' => 'product', 'name' => ['en' => 'Phones', 'bn' => 'ফোন']]);
        Role::findOrCreate('Super Admin', 'admin');
        $this->admin = Admin::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret123']);
        $this->admin->assignRole('Super Admin');
    }

    // ── Attributes ─────────────────────────────────────────────────

    public function test_attributes_and_values_used_by_variants_cannot_be_deleted(): void
    {
        $color = Attribute::create(['name' => 'Color', 'type' => 'select']);
        $red = $color->values()->create(['value' => 'Red']);
        $blue = $color->values()->create(['value' => 'Blue']);
        $variant = $this->product()->variants()->first();
        $variant->attributeValues()->attach($red->id);

        $this->actingAs($this->admin, 'admin')->delete(route('admin.attributes.destroy', $color))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'used by 1 product variant'));
        $this->actingAs($this->admin, 'admin')->delete(route('admin.attributes.values.destroy', $red))
            ->assertSessionHas('error');

        $this->assertModelExists($color);
        $this->assertSame([$red->id], $variant->attributeValues()->pluck('attribute_values.id')->all(), 'The variant keeps its option.');

        $this->actingAs($this->admin, 'admin')->delete(route('admin.attributes.values.destroy', $blue))->assertSessionHas('success');
        $this->assertModelMissing($blue);
    }

    public function test_an_attribute_cannot_get_the_same_value_twice(): void
    {
        $color = Attribute::create(['name' => 'Color', 'type' => 'select']);
        $color->values()->create(['value' => 'Red']);

        $this->actingAs($this->admin, 'admin')->post(route('admin.attributes.values.store', $color), ['value' => ' Red '])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already has'));
        $this->assertSame(1, $color->values()->count());
    }

    // ── Products ───────────────────────────────────────────────────

    public function test_product_edit_validates_every_field_and_defaults_the_low_stock_threshold(): void
    {
        $product = $this->product();
        $variant = $product->variants()->first();
        $payload = $this->productPayload(['variants' => [['id' => $variant->id, 'stock' => 10, 'original_stock' => 10]]]);

        $this->actingAs($this->admin, 'admin')->put(route('admin.products.update', $product), ['low_stock_threshold' => 'many', 'warranty_period' => str_repeat('x', 150)] + $payload)
            ->assertSessionHasErrors(['low_stock_threshold', 'warranty_period']);

        $this->actingAs($this->admin, 'admin')->put(route('admin.products.update', $product), ['low_stock_threshold' => ''] + $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame(5, $product->fresh()->low_stock_threshold);
    }

    public function test_variants_must_have_distinct_options(): void
    {
        $color = Attribute::create(['name' => 'Color', 'type' => 'select']);
        $red = $color->values()->create(['value' => 'Red']);
        $blue = $color->values()->create(['value' => 'Blue']);

        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->productPayload(['variants' => [
            ['stock' => 5, 'attribute_value_ids' => [$red->id]],
            ['stock' => 3, 'attribute_value_ids' => [$red->id]],
        ]]))->assertSessionHasErrors(['variants.1.attribute_value_ids' => 'Variant 2 has the same options as variant 1.']);

        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->productPayload(['variants' => [
            ['stock' => 5, 'attribute_value_ids' => [$red->id, $blue->id]],
        ]]))->assertSessionHasErrors(['variants.0.attribute_value_ids' => 'Pick only one Color per variant.']);

        $this->assertSame(0, Product::count());

        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->productPayload(['variants' => [
            ['stock' => 5, 'attribute_value_ids' => [$red->id]],
            ['stock' => 3, 'attribute_value_ids' => [$blue->id]],
        ]]))->assertSessionHasNoErrors();
        $this->assertSame(2, Product::sole()->variants()->count());
    }

    public function test_product_discount_applies_to_variants_with_their_own_price(): void
    {
        $product = $this->product(price: 1000, discount: 10);
        $own = $product->variants()->create(['sku' => 'OWN-1', 'price' => 1500, 'stock' => 3, 'is_active' => true]);
        $inherit = $product->variants()->first();

        $this->assertSame(1350.0, $own->fresh()->unitPrice());
        $this->assertSame(900.0, $inherit->fresh()->unitPrice());
    }

    // ── Cart pricing ───────────────────────────────────────────────

    public function test_checkout_never_charges_a_stale_cart_price(): void
    {
        $product = $this->product(price: 1000);
        $user = User::create(['name' => 'Rahim', 'email' => 'rahim@example.com', 'password' => 'secret123', 'phone' => '01712345678']);
        $this->actingAs($user)->postJson('/en/cart/add', ['product_id' => $product->id])->assertOk();
        $this->get('/en/checkout')->assertOk();

        $product->update(['price' => 1200, 'discounted_price' => 1200]);

        $order = ['name' => 'Rahim', 'email' => 'rahim@example.com', 'phone' => '01712345678', 'shipping_address' => 'House 1',
            'city' => 'Other', 'payment_method' => 'cod', 'checkout_token' => session('checkout_token')];

        $this->post('/en/place-order', $order)
            ->assertRedirect(route('checkout.index', ['locale' => 'en']))
            ->assertSessionHas('cart_changes', fn ($changes) => str_contains($changes[0], 'changed from'));
        $this->assertSame(0, Order::count());

        $this->get('/en/checkout')->assertOk()->assertSee('Your cart was updated');

        $this->post('/en/place-order', $order)->assertRedirect();
        $this->assertSame('1200.00', number_format((float) Order::sole()->orderItems()->value('price'), 2, '.', ''));
    }

    public function test_cart_removes_sold_out_and_archived_items(): void
    {
        $soldOut = $this->product(sku: 'SO-1');
        $archived = $this->product(sku: 'AR-1');
        $this->postJson('/en/cart/add', ['product_id' => $soldOut->id]);
        $this->postJson('/en/cart/add', ['product_id' => $archived->id]);

        $soldOut->variants()->update(['stock' => 0]);
        $archived->delete();

        $this->get('/en/cart')->assertOk()->assertSee('sold out')->assertSee('no longer available');
        $this->assertSame([], session('cart'));
    }

    // ── Categories ─────────────────────────────────────────────────

    public function test_subcategories_need_a_top_level_parent_and_keep_a_bangla_name(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('subcategories.store'), ['category_id' => $this->category->id, 'name' => 'Flagship', 'name_bn' => 'ফ্ল্যাগশিপ'])
            ->assertSessionHasNoErrors();
        $sub = Category::where('parent_id', $this->category->id)->sole();
        $this->assertSame('ফ্ল্যাগশিপ', $sub->getTranslation('name', 'bn'));

        $this->actingAs($this->admin, 'admin')->put(route('subcategories.update', $sub), ['category_id' => $sub->id, 'name' => 'Loop'])
            ->assertSessionHasErrors('category_id');
        $this->actingAs($this->admin, 'admin')->post(route('subcategories.store'), ['category_id' => $sub->id, 'name' => 'Third level'])
            ->assertSessionHasErrors('category_id');

        $this->assertSame($this->category->id, $sub->fresh()->parent_id);
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function product(float $price = 1000, float $discount = 0, string $sku = 'TP-1'): Product
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => ['en' => 'Test Phone ' . $sku, 'bn' => 'টেস্ট ফোন'],
            'description' => ['en' => '', 'bn' => ''],
            'price' => $price,
            'discount' => $discount,
            'discounted_price' => $price - $price * $discount / 100,
        ]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => $sku, 'stock' => 10, 'is_active' => true]);

        return $product;
    }

    private function productPayload(array $overrides = []): array
    {
        return $overrides + [
            'name_en' => 'Galaxy S24',
            'category_id' => $this->category->id,
            'price' => 1000,
            'discount' => 0,
        ];
    }
}
