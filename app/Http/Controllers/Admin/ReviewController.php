<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductComment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(ProductComment::STATUSES)],
            'rating' => 'nullable|integer|min:1|max:5',
            'search' => 'nullable|string|max:100',
        ]);
        $status = $filters['status'] ?? ProductComment::STATUS_PENDING;

        // Admins see every review, including those of blocked customers.
        $base = ProductComment::withoutGlobalScope('active_user');

        $reviews = (clone $base)
            ->with(['user', 'product:id,name,rating_avg,rating_count', 'moderator'])
            ->where('status', $status)
            ->when($filters['rating'] ?? null, fn ($q, $rating) => $q->where('rating', $rating))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('comment', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.reviews.index', compact('reviews', 'counts', 'status', 'filters'));
    }

    public function approve(int $id)
    {
        $this->moderate([$id], ProductComment::STATUS_APPROVED);

        return back()->with('success', 'Review approved and published.');
    }

    public function reject(int $id)
    {
        $this->moderate([$id], ProductComment::STATUS_REJECTED);

        return back()->with('success', 'Review rejected.');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'    => 'required|array|min:1|max:200',
            'ids.*'  => 'integer',
            'action' => 'required|in:approve,reject',
        ]);

        $count = $this->moderate($data['ids'], $data['action'] === 'approve' ? ProductComment::STATUS_APPROVED : ProductComment::STATUS_REJECTED);

        return back()->with('success', "{$count} review(s) " . ($data['action'] === 'approve' ? 'approved.' : 'rejected.'));
    }

    public function destroy(int $id)
    {
        ProductComment::withoutGlobalScope('active_user')->findOrFail($id)->delete();

        return back()->with('success', 'Review deleted.');
    }

    /**
     * Saved one by one so model events refresh each product's cached rating.
     */
    private function moderate(array $ids, string $status): int
    {
        $reviews = ProductComment::withoutGlobalScope('active_user')->whereIn('id', $ids)->get();

        foreach ($reviews as $review) {
            $review->update([
                'status' => $status,
                'moderated_by' => auth('admin')->id(),
                'moderated_at' => now(),
            ]);
        }

        return $reviews->count();
    }
}
