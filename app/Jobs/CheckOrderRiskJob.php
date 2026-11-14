<?php

namespace App\Jobs;

use App\Enums\RiskLevel;
use App\Models\Order;
use App\Services\AdminNotifier;
use App\Services\Fraud\FraudSettings;
use App\Services\FraudCheckerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs after checkout so the customer never waits for a courier API.
 */
class CheckOrderRiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** API failures already end as "Unknown" inside the service; retrying would only delay that. */
    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $orderId)
    {
    }

    public function handle(FraudCheckerService $checker): void
    {
        $order = Order::find($this->orderId);

        if (!$order || $order->risk_checked_at || !FraudSettings::checkEnabled()) {
            return;
        }

        $checker->checkOrder($order);
    }

    public function failed(Throwable $e): void
    {
        $order = Order::find($this->orderId);
        if (!$order) {
            return;
        }

        $order->forceFill(['risk_level' => RiskLevel::Unknown->value, 'risk_checked_at' => now()])->save();
        Log::error('Order risk check job failed', ['order' => $order->order_id, 'error' => $e->getMessage()]);
        app(AdminNotifier::class)->riskCheckFailed($order, 'The background check crashed: ' . $e->getMessage());
    }
}
