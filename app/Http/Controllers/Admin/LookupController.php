<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON search endpoints behind the admin's searchable pickers, so forms never load every row.
 */
class LookupController extends Controller
{
    private const LIMIT = 20;

    public function variants(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q'        => 'nullable|string|max:100',
            'inactive' => 'nullable|boolean',
            'ids'      => 'nullable|array|max:50',
            'ids.*'    => 'integer',
        ]);

        $withInactive = (bool) ($data['inactive'] ?? false);
        $term = trim($data['q'] ?? '');

        $variants = ProductVariant::query()
            ->with(['product' => fn ($q) => $q->withTrashed()->with('brand')])
            ->when(!$withInactive, fn (Builder $q) => $q->where('is_active', true)->whereHas('product'))
            ->when($withInactive, fn (Builder $q) => $q->whereHas('product', fn ($p) => $p->withTrashed()))
            ->when($data['ids'] ?? null, fn (Builder $q, array $ids) => $q->whereIn('id', $ids))
            ->when($term !== '', function (Builder $q) use ($term) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
                $q->where(fn (Builder $q) => $q
                    ->where('sku', 'like', $like)
                    ->orWhereHas('product', fn ($p) => $p->withTrashed()->where(fn ($p) => $p
                        ->where('name', 'like', $like)
                        ->orWhere('product_id', 'like', $like)
                        ->orWhere('model', 'like', $like)
                        ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like)))));
            })
            ->orderByDesc('stock')
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();

        return response()->json($variants->map(fn (ProductVariant $v) => $v->toPickerArray()));
    }

    public function customers(Request $request): JsonResponse
    {
        $term = trim((string) ($request->validate(['q' => 'nullable|string|max:100'])['q'] ?? ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

        $users = User::query()
            ->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'email', 'phone', 'address']);

        return response()->json($users);
    }
}
