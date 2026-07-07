<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $variants = ProductVariant::with('product')->orderBy('id')->get();
        $movements = StockMovement::with(['variant.product', 'admin'])->latest()->paginate(30);

        return view('admin.product.stock_movements.index', compact('variants', 'movements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'change' => 'required|integer|not_in:0',
            'type' => 'nullable|string',
            'reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data) {
            $variant = ProductVariant::findOrFail($data['variant_id']);

            $movement = StockMovement::create([
                'variant_id' => $variant->id,
                'change' => intval($data['change']),
                'type' => $data['type'] ?? 'adjustment',
                'reason' => $data['reason'] ?? null,
                'source_type' => null,
                'source_id' => null,
                'admin_id' => null,
            ]);

            // apply change
            $variant->stock = max(0, $variant->stock + intval($data['change']));
            $variant->save();
        });

        return back()->with('success', 'Stock adjusted successfully.');
    }
}
