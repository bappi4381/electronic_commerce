<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use RuntimeException;

class InvalidOrderTransitionException extends RuntimeException
{
    public function __construct(public readonly ?OrderStatus $from, public readonly OrderStatus $to, string $orderId = '')
    {
        parent::__construct(sprintf(
            'Order %s cannot move from %s to %s.',
            $orderId,
            $from?->label() ?? 'unknown',
            $to->label(),
        ));
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

        return back()->withErrors(['status' => $this->getMessage()]);
    }
}
