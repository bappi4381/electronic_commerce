<?php

namespace App\Notifications\Admin;

use App\Models\ProductComment;
use Illuminate\Support\Str;

class NewReviewSubmitted extends AdminNotification
{
    public function __construct(ProductComment $review)
    {
        $review->loadMissing('product');

        $this->data = [
            'kind'  => 'review',
            'title' => 'Review awaiting approval',
            'body'  => str_repeat('★', (int) $review->rating) . ' ' . ($review->product?->name ?? 'Product') . ' · ' . Str::limit((string) $review->comment, 60),
            'url'   => route('admin.reviews.index', [], false),
            'icon'  => 'bi-star-fill',
            'color' => 'amber',
        ];
    }
}
