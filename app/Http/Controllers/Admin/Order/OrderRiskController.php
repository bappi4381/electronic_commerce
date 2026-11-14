<?php

namespace App\Http\Controllers\Admin\Order;

use App\Enums\OrderStatus;
use App\Enums\RiskLevel;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PhoneBlacklist;
use App\Services\FraudCheckerService;
use App\Services\OrderStatusService;
use App\Support\BdPhone;
use Illuminate\Http\Request;

class OrderRiskController extends Controller
{
    public function __construct(private OrderStatusService $orderStatus)
    {
    }

    /** Fresh lookup, bypassing the 24-hour cache. */
    public function recheck(Order $order, FraudCheckerService $checker)
    {
        $log = $checker->checkOrder($order, fresh: true, adminId: auth('admin')->id());
        $level = $log->levelEnum() ?? RiskLevel::Unknown;

        $this->audit($order, 'Re-checked delivery risk', ['risk_level' => $level->value, 'source' => $log->source]);

        if ($level === RiskLevel::Unknown) {
            return back()->with('error', 'The risk check failed: ' . ($log->error() ?? 'unknown error') . ' The order was not changed.');
        }

        return back()->with('success', "Risk re-checked: {$level->label()}" . ($log->success_rate !== null ? " ({$log->success_rate}% delivered)." : '.'));
    }

    /** The admin vouches for the order: release it from hold and stop asking for an advance. */
    public function markSafe(Request $request, Order $order)
    {
        $data = $request->validate(['note' => 'nullable|string|max:255']);
        $adminId = auth('admin')->id();

        $order->forceFill(['risk_reviewed_at' => now(), 'risk_reviewed_by' => $adminId, 'advance_amount' => null])->save();

        if ($order->statusEnum() === OrderStatus::OnHold) {
            try {
                $this->orderStatus->transition($order, OrderStatus::Pending, $adminId, 'Marked as safe' . (!empty($data['note']) ? ': ' . $data['note'] : ''));
            } catch (InvalidOrderTransitionException|InsufficientStockException $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        $this->audit($order, 'Marked order as safe', ['note' => $data['note'] ?? null]);

        return back()->with('success', "Order {$order->order_id} marked as safe" . ($order->statusEnum() === OrderStatus::OnHold ? ' and released from hold.' : '.'));
    }

    public function blacklist(Request $request, Order $order)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
            'cancel_order' => 'nullable|boolean',
        ]);

        $phone = $order->phone_normalized ?: BdPhone::normalize($order->customerDisplayPhone());
        if (!$phone) {
            return back()->with('error', 'This order has no valid Bangladeshi mobile number to blacklist.');
        }

        PhoneBlacklist::updateOrCreate(['phone' => $phone], ['reason' => $data['reason'], 'admin_id' => auth('admin')->id()]);
        $this->audit($order, 'Blacklisted the order\'s phone number', ['phone' => BdPhone::mask($phone), 'reason' => $data['reason']]);

        $message = 'Number ' . BdPhone::mask($phone) . ' blacklisted. New orders from it will be refused.';

        if ($request->boolean('cancel_order') && $order->statusEnum()?->canTransitionTo(OrderStatus::Cancelled)) {
            try {
                $this->orderStatus->transition($order, OrderStatus::Cancelled, auth('admin')->id(), 'Phone blacklisted: ' . $data['reason']);
                $message .= ' The order was cancelled.';
            } catch (InvalidOrderTransitionException|InsufficientStockException $e) {
                $message .= ' The order could not be cancelled: ' . $e->getMessage();
            }
        }

        return back()->with('success', $message);
    }

    private function audit(Order $order, string $description, array $properties = []): void
    {
        activity('fraud')
            ->performedOn($order)
            ->causedBy(auth('admin')->user())
            ->withProperties(['order' => $order->order_id] + $properties)
            ->log($description);
    }
}
