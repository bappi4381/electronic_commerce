<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\RiskLevel;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\CustomerRiskLog;
use App\Models\Order;
use App\Services\Fraud\Drivers\BdCourierDriver;
use App\Services\Fraud\Drivers\LocalHistoryDriver;
use App\Services\Fraud\Drivers\SteadfastFraudDriver;
use App\Services\Fraud\FraudCheckException;
use App\Services\Fraud\FraudDriver;
use App\Services\Fraud\FraudSettings;
use App\Services\Fraud\RiskResult;
use App\Support\BdPhone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Courier delivery history for a phone number, turned into a risk level:
 * Low (>= 80% delivered), Medium (50–79%), High (< 50% or too many cancellations), New (no history),
 * Unknown (the source failed: never blocks an order, admins are told instead).
 */
class FraudCheckerService
{
    public function __construct(private OrderStatusService $orderStatus, private AdminNotifier $notifier)
    {
    }

    public function driver(?string $name = null): FraudDriver
    {
        return match ($name ?? FraudSettings::driver()) {
            'steadfast' => app(SteadfastFraudDriver::class),
            'bdcourier' => app(BdCourierDriver::class),
            default => app(LocalHistoryDriver::class),
        };
    }

    /** Cached for 24 hours per source and number; $fresh skips the cache (admin "Re-check"). */
    public function check(string $phone, bool $fresh = false, ?int $excludeOrderId = null): RiskResult
    {
        $driver = $this->driver();
        $normalized = BdPhone::normalize($phone);

        if (!$normalized) {
            return RiskResult::unknown($driver->name(), 'Not a valid Bangladeshi mobile number.');
        }

        $cacheKey = 'fraud-check:' . $driver->name() . ':' . hash('sha256', $normalized);

        if (!$fresh && is_array($cached = Cache::get($cacheKey))) {
            return RiskResult::fromArray($cached, cached: true);
        }

        try {
            $data = $driver->lookup($normalized, $excludeOrderId);
        } catch (FraudCheckException $e) {
            return $this->failed($driver, $normalized, $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->failed($driver, $normalized, 'The check failed unexpectedly.');
        }

        $result = $this->classify($data['total'], $data['delivered'], $data['cancelled'], $driver->name(), $data['breakdown'], $data['raw']);
        Cache::put($cacheKey, $result->toArray(), now()->addHours((int) config('fraud.cache_hours', 24)));

        return $result;
    }

    public function classify(int $total, int $delivered, int $cancelled, string $source, array $breakdown = [], array $raw = []): RiskResult
    {
        $total = max($total, $delivered + $cancelled);

        if ($total === 0) {
            return new RiskResult(RiskLevel::New, 0, 0, 0, null, $source, $breakdown, $raw);
        }

        $rate = round($delivered / $total * 100, 2);

        $level = match (true) {
            $cancelled >= FraudSettings::highCancelCount() || $rate < (float) config('fraud.medium_from', 50) => RiskLevel::High,
            $rate >= (float) config('fraud.low_from', 80) => RiskLevel::Low,
            default => RiskLevel::Medium,
        };

        return new RiskResult($level, $total, $delivered, $cancelled, $rate, $source, $breakdown, $raw);
    }

    /**
     * Check the order's phone, store the result on the order and in customer_risk_logs,
     * then hold High-risk cash-on-delivery orders and alert admins.
     */
    public function checkOrder(Order $order, bool $fresh = false, ?int $adminId = null): CustomerRiskLog
    {
        $phone = $order->phone_normalized ?: BdPhone::normalize($order->customerDisplayPhone());
        $result = $phone
            ? $this->check($phone, $fresh, $order->id)
            : RiskResult::unknown(FraudSettings::driver(), 'The order has no valid Bangladeshi mobile number.');

        $log = CustomerRiskLog::create([
            'phone' => $phone ?? '',
            'order_id' => $order->id,
            'total_orders' => $result->total,
            'delivered' => $result->delivered,
            'cancelled' => $result->cancelled,
            'success_rate' => $result->successRate,
            'source' => $result->source,
            'risk_level' => $result->level->value,
            'raw_response' => ['stats' => $result->raw, 'breakdown' => $result->breakdown, 'error' => $result->error, 'cached' => $result->cached],
            'admin_id' => $adminId,
            'checked_at' => now(),
        ]);

        $order->forceFill(['risk_level' => $result->level->value, 'risk_checked_at' => now()])->save();

        Log::info('Order risk checked', [
            'order' => $order->order_id,
            'level' => $result->level->value,
            'source' => $result->source,
            'cached' => $result->cached,
            'by_admin' => $adminId,
        ]);

        $this->applyConsequences($order, $result, $adminId);

        return $log;
    }

    /** Whether this order is cash collected by the courier (only those are put on hold or asked for an advance). */
    public function isUnpaidCod(Order $order): bool
    {
        return $order->payment_method === 'cod' && !$order->paymentStatusEnum()?->isCollected();
    }

    private function applyConsequences(Order $order, RiskResult $result, ?int $adminId): void
    {
        if ($result->level === RiskLevel::Unknown) {
            $this->notifier->riskCheckFailed($order, (string) $result->error);

            return;
        }

        // An admin already reviewed this order and marked it safe: record the result, change nothing.
        if ($result->level !== RiskLevel::High || $order->risk_reviewed_at) {
            return;
        }

        $held = false;

        if ($this->isUnpaidCod($order)) {
            if (FraudSettings::advanceEnabled() && $order->advance_amount === null) {
                $amount = FraudSettings::advanceType() === 'fixed' ? FraudSettings::advanceAmount() : (float) $order->delivery_charge;
                if ($amount > 0) {
                    $order->forceFill(['advance_amount' => min($amount, (float) $order->total_price)])->save();
                }
            }

            if (FraudSettings::holdHighRisk() && $order->statusEnum() === OrderStatus::Pending) {
                try {
                    $this->orderStatus->transition($order, OrderStatus::OnHold, $adminId, sprintf(
                        'High delivery risk: %s%% delivered (%d of %d), %d cancelled [%s]',
                        rtrim(rtrim(number_format((float) $result->successRate, 2), '0'), '.'),
                        $result->delivered, $result->total, $result->cancelled, $result->source,
                    ));
                    $held = true;
                } catch (InvalidOrderTransitionException|InsufficientStockException $e) {
                    Log::warning('High-risk order could not be put on hold', ['order' => $order->order_id, 'reason' => $e->getMessage()]);
                }
            }
        }

        $this->notifier->riskyOrder($order->fresh(), $result, $held);
    }

    private function failed(FraudDriver $driver, string $phone, string $error): RiskResult
    {
        Log::warning('Fraud check failed', ['source' => $driver->name(), 'phone' => BdPhone::mask($phone), 'error' => $error]);

        return RiskResult::unknown($driver->name(), $error);
    }
}
