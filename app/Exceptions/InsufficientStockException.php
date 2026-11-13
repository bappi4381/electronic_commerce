<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly ProductVariant $variant,
        public readonly int $requested,
        string $message = '',
    ) {
        parent::__construct($message ?: sprintf(
            'Not enough stock for %s (requested %d, available %d).',
            $variant->label(),
            $requested,
            max(0, (int) $variant->stock),
        ));
    }

    public static function unavailable(ProductVariant $variant, int $requested): self
    {
        return new self($variant, $requested, sprintf('%s is no longer available.', $variant->label()));
    }

    public function report(): bool
    {
        return false;
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->withInput()->withErrors(['stock' => $this->getMessage()]);
    }
}
