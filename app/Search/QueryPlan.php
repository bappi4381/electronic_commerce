<?php

namespace App\Search;

/**
 * A normalised query and the variants sent to the engine, best first.
 * Each variant: ['q' => string, 'weight' => float, 'kind' => original|compact|banglish|transliterated].
 */
final class QueryPlan
{
    public function __construct(
        public readonly string $raw,
        public readonly string $normalized,
        public readonly string $script,
        public readonly array $variants,
    ) {
    }

    public static function make(string $raw, Dictionary $dictionary): self
    {
        $raw = mb_substr(trim($raw), 0, 100);
        $normalized = BanglaText::normalize($raw);
        $script = BanglaText::script($normalized);
        $content = implode(' ', $dictionary->contentWords($normalized));

        $variants = [];
        $add = function (string $q, float $weight, string $kind) use (&$variants) {
            $q = trim($q);
            if ($q !== '' && !isset($variants[$q])) {
                $variants[$q] = ['q' => $q, 'weight' => $weight, 'kind' => $kind];
            }
        };

        $add($content, 1.0, 'original');

        if ($script === BanglaText::SCRIPT_LATIN || $script === BanglaText::SCRIPT_MIXED) {
            if (count(BanglaText::words($content)) > 1 || str_contains($content, '-')) {
                $add(BanglaText::compact($content), 0.9, 'compact');
            }
            $add(Transliterator::key($content), 0.8, 'banglish');
        }

        if ($script === BanglaText::SCRIPT_BANGLA || $script === BanglaText::SCRIPT_MIXED) {
            $latin = Transliterator::toLatin($content);
            $add($latin, 0.85, 'transliterated');
            $add(Transliterator::key($latin), 0.8, 'banglish');
            if (count(BanglaText::words($content)) > 1) {
                $add(BanglaText::compact($content), 0.9, 'compact');
            }
        }

        return new self($raw, $normalized, $script, array_values($variants));
    }

    public function isEmpty(): bool
    {
        return $this->normalized === '' || $this->variants === [];
    }

    /** Bangla queries suggest from 1 character, Latin ones from 2. */
    public function isTooShort(): bool
    {
        $length = mb_strlen(BanglaText::compact($this->normalized));

        return $length === 0 || ($length < 2 && $this->script !== BanglaText::SCRIPT_BANGLA);
    }

    /** Scripts the store does not use (e.g. Devanagari) have no catalog text to match. */
    public function isUnsupportedScript(): bool
    {
        return $this->script === BanglaText::SCRIPT_OTHER && !preg_match('/\d/', $this->normalized);
    }
}
