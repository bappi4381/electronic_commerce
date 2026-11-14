<?php

namespace App\Console\Commands;

use App\Services\FacebookCapiService;
use Illuminate\Console\Command;
use Throwable;

class FacebookTestEvent extends Command
{
    protected $signature = 'fb:test-event
        {--event=PageView : Event name, e.g. PageView, ViewContent, AddToCart, InitiateCheckout, Purchase}
        {--code= : Test event code from Events Manager › Test events (defaults to the one in Admin › Settings › Tracking)}
        {--live : Send without a test event code (counts as real data)}';

    protected $description = 'Send one dummy Conversions API event to Meta and print the response';

    public function handle(FacebookCapiService $capi): int
    {
        if (!$capi->pixelId() || !$capi->accessToken()) {
            $this->error('Add the Pixel ID and CAPI access token in Admin › Settings › Tracking (or FB_PIXEL_ID / FB_CAPI_ACCESS_TOKEN in .env).');

            return self::FAILURE;
        }

        $code = $this->option('live') ? '' : ($this->option('code') ?: $capi->testEventCode());
        if ($code === null) {
            $this->error('No test event code. Add one in Admin › Settings › Tracking, pass --code=TEST12345, or use --live to send real data.');

            return self::FAILURE;
        }

        $event = (string) $this->option('event');
        $this->line("Endpoint:   {$capi->endpoint()}");
        $this->line("Event:      {$event}");
        $this->line('Test code:  ' . ($code !== '' ? $code : '(none, live data)'));
        $this->line('User data:  ' . json_encode($capi->testUserData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        try {
            $response = $capi->sendTestEvent($event, $code);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("HTTP {$response->status()}");
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info($code !== '' ? 'Open Events Manager › Test events: the event should appear within a minute.' : 'Sent as live data.');

        return self::SUCCESS;
    }
}
