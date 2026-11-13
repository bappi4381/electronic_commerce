<?php

namespace App\Search;

use Normalizer;

/**
 * Text normalisation applied identically to indexed text and to queries.
 */
class BanglaText
{
    public const SCRIPT_BANGLA = 'bn';
    public const SCRIPT_LATIN = 'latin';
    public const SCRIPT_MIXED = 'mixed';
    public const SCRIPT_OTHER = 'other';

    private const INVISIBLE = ["\u{200B}", "\u{200C}", "\u{200D}", "\u{2060}", "\u{FEFF}", "\u{00AD}"];

    // NFC leaves these decomposed (they are composition exclusions); index and queries use the precomposed letter.
    private const NUKTA = [
        "\u{09A1}\u{09BC}" => "\u{09DC}", // ড় RRA
        "\u{09A2}\u{09BC}" => "\u{09DD}", // ঢ় RHA
        "\u{09AF}\u{09BC}" => "\u{09DF}", // য় YYA
    ];

    private const BANGLA_DIGITS = ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'];

    public static function normalize(?string $text): string
    {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }

        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        if (class_exists(Normalizer::class)) {
            $text = Normalizer::normalize($text, Normalizer::FORM_C) ?: $text;
        }

        $text = str_replace(self::INVISIBLE, '', $text);
        $text = strtr($text, self::NUKTA);
        $text = strtr($text, self::BANGLA_DIGITS);
        $text = str_replace(['’', '‘', '`', '´'], "'", $text);
        $text = mb_strtolower($text, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /** "T-Shirt" => "tshirt", "রাইস কুকার" => "রাইসকুকার". */
    public static function compact(?string $text): string
    {
        return preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '', static::normalize($text));
    }

    /** Words made of letters, marks and digits; punctuation and hyphens separate words. */
    public static function words(?string $text): array
    {
        $parts = preg_split('/[^\p{L}\p{M}\p{N}]+/u', static::normalize($text), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($parts, fn ($w) => $w !== ''));
    }

    public static function script(?string $text): string
    {
        $text = (string) $text;
        $bangla = preg_match_all('/[\x{0980}-\x{09FF}]/u', $text);
        $latin = preg_match_all('/[a-zA-Z]/', $text);

        return match (true) {
            $bangla > 0 && $latin > 0 => self::SCRIPT_MIXED,
            $bangla > 0 => self::SCRIPT_BANGLA,
            $latin > 0 => self::SCRIPT_LATIN,
            preg_match('/\p{L}/u', $text) === 1 => self::SCRIPT_OTHER,
            default => preg_match('/\d/', $text) ? self::SCRIPT_LATIN : self::SCRIPT_OTHER,
        };
    }

    public static function hasBangla(?string $text): bool
    {
        return (bool) preg_match('/[\x{0980}-\x{09FF}]/u', (string) $text);
    }

    /**
     * Edit distance on characters where swapping two adjacent letters is one typo, as in Meilisearch
     * (PHP's levenshtein() counts bytes, which breaks for Bangla, and counts a swap as two).
     */
    public static function distance(string $a, string $b, int $max = PHP_INT_MAX): int
    {
        if ($a === $b) {
            return 0;
        }

        $x = mb_str_split($a);
        $y = mb_str_split($b);
        $n = count($x);
        $m = count($y);
        if (abs($n - $m) > $max) {
            return $max + 1;
        }

        $d = [];
        for ($i = 0; $i <= $n; $i++) {
            $d[$i][0] = $i;
        }
        for ($j = 0; $j <= $m; $j++) {
            $d[0][$j] = $j;
        }

        for ($i = 1; $i <= $n; $i++) {
            $rowMin = PHP_INT_MAX;
            for ($j = 1; $j <= $m; $j++) {
                $cost = $x[$i - 1] === $y[$j - 1] ? 0 : 1;
                $d[$i][$j] = min($d[$i - 1][$j] + 1, $d[$i][$j - 1] + 1, $d[$i - 1][$j - 1] + $cost);
                if ($i > 1 && $j > 1 && $x[$i - 1] === $y[$j - 2] && $x[$i - 2] === $y[$j - 1]) {
                    $d[$i][$j] = min($d[$i][$j], $d[$i - 2][$j - 2] + 1);
                }
                $rowMin = min($rowMin, $d[$i][$j]);
            }
            if ($rowMin > $max) {
                return $max + 1;
            }
        }

        return $d[$n][$m];
    }

    /** Typos Meilisearch would allow for a word of this length (settings: oneTypo=4, twoTypos=8). */
    public static function allowedTypos(string $word): int
    {
        $length = mb_strlen($word);

        return $length >= 8 ? 2 : ($length >= 4 ? 1 : 0);
    }
}
