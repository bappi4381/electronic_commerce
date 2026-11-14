<?php

namespace App\Services\Fraud;

use App\Enums\RiskLevel;

/**
 * Outcome of one delivery-history check. Cached as an array for 24 hours (Unknown results are never cached).
 */
final class RiskResult
{
    /**
     * @param  array<string, array{total: int, delivered: int, cancelled: int}>  $breakdown  per courier
     */
    public function __construct(
        public readonly RiskLevel $level,
        public readonly int $total,
        public readonly int $delivered,
        public readonly int $cancelled,
        public readonly ?float $successRate,
        public readonly string $source,
        public readonly array $breakdown = [],
        public readonly array $raw = [],
        public readonly ?string $error = null,
        public readonly bool $cached = false,
    ) {
    }

    public static function unknown(string $source, string $error): self
    {
        return new self(RiskLevel::Unknown, 0, 0, 0, null, $source, error: $error);
    }

    public function toArray(): array
    {
        return [
            'level' => $this->level->value,
            'total' => $this->total,
            'delivered' => $this->delivered,
            'cancelled' => $this->cancelled,
            'success_rate' => $this->successRate,
            'source' => $this->source,
            'breakdown' => $this->breakdown,
            'raw' => $this->raw,
            'error' => $this->error,
        ];
    }

    public static function fromArray(array $data, bool $cached = false): self
    {
        return new self(
            RiskLevel::from($data['level']),
            (int) $data['total'],
            (int) $data['delivered'],
            (int) $data['cancelled'],
            $data['success_rate'] === null ? null : (float) $data['success_rate'],
            (string) $data['source'],
            $data['breakdown'] ?? [],
            $data['raw'] ?? [],
            $data['error'] ?? null,
            $cached,
        );
    }
}
