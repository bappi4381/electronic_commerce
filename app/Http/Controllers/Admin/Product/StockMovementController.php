<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Services\StockService;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['variant' => 'nullable|integer']);

        $movements = StockMovement::with(['variant.product' => fn ($query) => $query->withTrashed(), 'admin'])
            ->when($filters['variant'] ?? null, fn ($q, $id) => $q->where('variant_id', $id))
            ->latest()
            ->paginate(30);

        $filterVariant = $this->pickerItem($filters['variant'] ?? null);
        // Pre-select the variant being filtered, or the one from a failed submit.
        $selectedVariant = $this->pickerItem(old('variant_id')) ?? $filterVariant;

        return view('admin.product.stock_movements.index', compact('movements', 'filterVariant', 'selectedVariant'));
    }

    public function store(Request $request, StockService $stock)
    {
        $data = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'change' => 'required|integer|not_in:0',
            'type' => 'nullable|in:adjustment,correction,return,other',
            'reason' => 'nullable|string|max:255',
        ]);

        $stock->adjust(
            (int) $data['variant_id'],
            (int) $data['change'],
            null,
            $data['reason'] ?? 'Manual stock adjustment',
            auth('admin')->id(),
            $data['type'] ?? 'adjustment',
        );

        return back()->with('success', 'Stock adjusted successfully.');
    }

    private function pickerItem(mixed $variantId): ?array
    {
        return $variantId
            ? ProductVariant::with(['product' => fn ($q) => $q->withTrashed()])->find($variantId)?->toPickerArray()
            : null;
    }
}
