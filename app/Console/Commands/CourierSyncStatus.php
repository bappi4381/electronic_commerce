<?php

namespace App\Console\Commands;

use App\Exceptions\CourierException;
use App\Models\OrderCourier;
use App\Services\Courier\CourierBookingService;
use App\Services\Courier\CourierStatus;
use Illuminate\Console\Command;

/**
 * Fallback for missed webhooks: asks the courier for the status of every parcel that is still moving.
 */
class CourierSyncStatus extends Command
{
    protected $signature = 'courier:sync-status
                            {--courier= : Only this courier (steadfast, pathao)}
                            {--limit=100 : Most parcels checked per run}
                            {--days=45 : Ignore parcels sent longer ago than this}';

    protected $description = 'Poll courier APIs for parcels that have not reached a final status';

    public function handle(CourierBookingService $couriers): int
    {
        $parcels = OrderCourier::query()
            ->with('order')
            ->whereNotNull('consignment_id')
            ->whereNotIn('status', CourierStatus::FINAL)
            ->where(fn ($q) => $q->where('sent_at', '>=', now()->subDays((int) $this->option('days')))
                ->orWhere(fn ($q) => $q->whereNull('sent_at')->where('created_at', '>=', now()->subDays((int) $this->option('days')))))
            ->when($this->option('courier'), fn ($q, $courier) => $q->where('courier_name', $courier))
            ->orderByRaw('status_checked_at IS NOT NULL, status_checked_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        if ($parcels->isEmpty()) {
            $this->info('No parcels to check.');

            return self::SUCCESS;
        }

        $changed = $failed = 0;

        foreach ($parcels as $parcel) {
            $before = $parcel->status;

            try {
                $parcel = $couriers->refresh($parcel, null, 'scheduled sync');
            } catch (CourierException $e) {
                $failed++;
                $this->warn("{$parcel->order?->order_id} ({$parcel->consignment_id}): {$e->getMessage()}");

                continue;
            }

            if ($parcel->status !== $before) {
                $changed++;
                $this->line("{$parcel->order?->order_id}: " . CourierStatus::label($before) . ' → ' . CourierStatus::label($parcel->status));
            }
        }

        $this->info("Checked {$parcels->count()} parcel(s): {$changed} changed, {$failed} failed.");

        return $failed === $parcels->count() ? self::FAILURE : self::SUCCESS;
    }
}
