<?php

namespace App\Jobs;

use App\Services\FacebookCapiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends one Conversions API event off the request cycle. user_data arrives already hashed
 * (see FacebookTracking), so no raw email or phone is stored in the jobs table.
 */
class SendFacebookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $customData
     */
    public function __construct(
        public string $eventName,
        public string $eventId,
        public array $userData,
        public array $customData = [],
        public ?string $sourceUrl = null,
        public ?int $eventTime = null,
    ) {
    }

    /** Seconds to wait before retry 2 and 3. */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(FacebookCapiService $capi): void
    {
        if (!$capi->enabled()) {
            return;
        }

        $response = $capi->sendEvent($this->eventName, $this->eventId, $this->userData, $this->customData, $this->sourceUrl, $this->eventTime ?? time());

        Log::debug('Facebook CAPI event sent', [
            'event' => $this->eventName,
            'event_id' => $this->eventId,
            'events_received' => $response->json('events_received'),
            'fbtrace_id' => $response->json('fbtrace_id'),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Facebook CAPI event failed after ' . $this->tries . ' attempts', [
            'event' => $this->eventName,
            'event_id' => $this->eventId,
            'error' => $exception->getMessage(),
        ]);
    }
}
