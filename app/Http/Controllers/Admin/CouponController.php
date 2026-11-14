<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Services\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $coupons = Coupon::withCount('usages')->latest()->get();
        $search = $request->input('search');

        // Get all products to manage their individual discounts with search support
        $products = Product::with('category')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->appends(['search' => $search]);

        $discountedProductsCount = Product::where('discount', '>', 0)->count();

        return view('admin.marketing.index', compact('coupons', 'products', 'discountedProductsCount'));
    }

    public function updateProductDiscount(Request $request, Product $product)
    {
        $request->validate([
            'discount' => 'required|numeric|min:0|max:100',
            'is_flash_deal' => 'nullable|boolean',
            'flash_discount' => 'nullable|numeric|min:0.01|max:100',
        ]);

        $flash = $request->boolean('is_flash_deal');
        $productName = is_array($product->name) ? ($product->name[app()->getLocale()] ?? $product->name['en'] ?? 'Product') : $product->getTranslation('name', 'en');

        // A separate flash % keeps the regular discount safe when the campaign ends.
        if ($flash && !$request->filled('flash_discount')) {
            return back()->with('error', "Enter the flash discount % for {$productName}.");
        }

        // While a flash discount is applied, the regular discount waits in pre_flash_discount.
        if ($product->pre_flash_discount !== null) {
            $product->pre_flash_discount = $request->discount;
        } else {
            $product->applyDiscount((float) $request->discount);
        }
        $product->is_flash_deal = $flash;
        $product->flash_discount = $flash && $request->filled('flash_discount') ? $request->flash_discount : null;
        $product->save();

        $flashSale = app(FlashSale::class);
        $flashSale->sync();

        $note = match (true) {
            !$flash => '',
            $flashSale->status() === FlashSale::STATUS_ENDED => ' The campaign has ended, so the flash price is not live: set new start and end times above.',
            $flashSale->status() === FlashSale::STATUS_SCHEDULED => ' The flash price goes live when the campaign starts.',
            default => ' The flash price is live now.',
        };

        return back()->with('success', 'Discount updated for ' . $productName . '.' . $note);
    }

    public function updateFlashDeal(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate(self::flashDealRules());

        Setting::set('flash_deal_title', $data['flash_deal_title']);
        Setting::set('flash_deal_start_time', $data['flash_deal_start_time'] ?? null);
        Setting::set('flash_deal_end_time', $data['flash_deal_end_time'] ?? null);
        Setting::set('flash_deal_auto_end', $request->boolean('flash_deal_auto_end') ? '1' : '0');
        $flashSale->sync();

        return back()->with('success', 'Flash deal schedule updated.');
    }

    /** "End campaign now": restore prices and take every product out of the campaign. */
    public function endFlashDeal(Request $request, FlashSale $flashSale)
    {
        $closed = $flashSale->end($request->boolean('remove_discounts', true));

        activity('marketing')->causedBy(auth('admin')->user())
            ->withProperties(['products' => $closed, 'remove_discounts' => $request->boolean('remove_discounts', true)])
            ->log('Ended the flash deal campaign');

        return back()->with('success', "Flash deal ended. {$closed} " . \Illuminate\Support\Str::plural('product', $closed) . ' left the campaign and regular prices are back.');
    }

    /** Shared with Settings › Campaigns. */
    public static function flashDealRules(bool $titleRequired = true): array
    {
        return [
            'flash_deal_title' => [$titleRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'flash_deal_start_time' => 'nullable|date',
            'flash_deal_end_time' => 'nullable|date|after:flash_deal_start_time',
            'flash_deal_auto_end' => 'nullable|boolean',
        ];
    }

    public function create()
    {
        return view('admin.marketing.coupon-form', ['coupon' => new Coupon(['type' => 'percent', 'status' => true])]);
    }

    public function store(Request $request)
    {
        Coupon::create($this->validated($request));

        return redirect()->route('marketing.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        $coupon->loadCount('usages');

        return view('admin.marketing.coupon-form', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validated($request, $coupon));

        return redirect()->route('marketing.index')->with('success', "Coupon {$coupon->code} updated.");
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return back()->with('success', 'Coupon deleted successfully.');
    }

    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update(['status' => !$coupon->status]);
        return back()->with('success', 'Coupon status updated.');
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type'             => 'required|in:fixed,percent',
            'value'            => ['required', 'numeric', 'min:0.01', $request->input('type') === 'percent' ? 'max:100' : 'max:9999999'],
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'usage_limit'      => ['nullable', 'integer', 'min:' . max(1, (int) $coupon?->used_count)],
            'per_user_limit'   => 'nullable|integer|min:1',
            'starts_at'        => 'nullable|date',
            'ends_at'          => array_filter([
                'nullable',
                'date',
                $request->filled('starts_at') ? 'after:starts_at' : null,
                $coupon ? null : 'after:now',
            ]),
            'status'           => 'nullable|boolean',
        ], [
            'code.regex' => 'Use letters, numbers, dashes or underscores only.',
            'usage_limit.min' => 'The usage limit cannot be below the number of times the coupon has already been used.',
        ]);

        $data['status'] = $request->boolean('status');
        $data['max_discount'] = $data['type'] === 'percent' ? ($data['max_discount'] ?? null) : null;

        return $data;
    }
}
