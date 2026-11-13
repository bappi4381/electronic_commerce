<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accepts a courier webhook only when the configured header carries either the
 * shared webhook secret (Bearer token for the Authorization header) or a hex
 * HMAC-SHA256 of the raw request body keyed with that secret.
 */
class VerifyCourierWebhook
{
    public function handle(Request $request, Closure $next, string $courier): Response
    {
        $secret = (string) config("services.{$courier}.webhook_secret");
        $header = (string) config("services.{$courier}.webhook_header", 'Authorization');

        $context = [
            'courier' => $courier,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
        ];

        if ($secret === '') {
            Log::error('Courier webhook rejected: webhook secret is not configured', $context);
            return response()->json(['message' => 'Webhook not configured'], 503);
        }

        $provided = strcasecmp($header, 'Authorization') === 0
            ? (string) $request->bearerToken()
            : (string) $request->header($header, '');

        if (!$this->isValid($provided, $secret, $request->getContent())) {
            Log::warning('Courier webhook rejected: invalid or missing signature', $context + [
                'signature_present' => $provided !== '',
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        Log::info('Courier webhook signature verified', $context);

        return $next($request);
    }

    private function isValid(string $provided, string $secret, string $payload): bool
    {
        if ($provided === '') {
            return false;
        }

        if (hash_equals($secret, $provided)) {
            return true;
        }

        $signature = strtolower(Str::after($provided, 'sha256='));

        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }
}
