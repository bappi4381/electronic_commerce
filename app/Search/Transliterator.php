<?php

namespace App\Search;

/**
 * Bangla => Banglish (the Latin spelling people actually type, e.g. "কেটলি" => "ketli",
 * "টি-শার্ট" => "ti shart"), plus a phonetic key that folds common Banglish spelling
 * variants together ("walton"/"ওয়ালটন", "laptop"/"ল্যাপটপ").
 */
class Transliterator
{
    private const CONSONANTS = [
        'ক' => 'k', 'খ' => 'kh', 'গ' => 'g', 'ঘ' => 'gh', 'ঙ' => 'ng',
        'চ' => 'ch', 'ছ' => 'chh', 'জ' => 'j', 'ঝ' => 'jh', 'ঞ' => 'n',
        'ট' => 't', 'ঠ' => 'th', 'ড' => 'd', 'ঢ' => 'dh', 'ণ' => 'n',
        'ত' => 't', 'থ' => 'th', 'দ' => 'd', 'ধ' => 'dh', 'ন' => 'n',
        'প' => 'p', 'ফ' => 'f', 'ব' => 'b', 'ভ' => 'bh', 'ম' => 'm',
        'য' => 'j', 'র' => 'r', 'ল' => 'l', 'শ' => 'sh', 'ষ' => 'sh',
        'স' => 's', 'হ' => 'h', 'ৎ' => 't',
        // Precomposed ড় ঢ় য়, which BanglaText::normalize() produces.
        "\u{09DC}" => 'r', "\u{09DD}" => 'rh', "\u{09DF}" => 'y',
        "\u{E000}" => 'y', // ya-phala (্য), substituted before parsing
    ];

    private const VOWELS = [
        'অ' => 'o', 'আ' => 'a', 'ই' => 'i', 'ঈ' => 'i', 'উ' => 'u', 'ঊ' => 'u',
        'ঋ' => 'ri', 'এ' => 'e', 'ঐ' => 'oi', 'ও' => 'o', 'ঔ' => 'ou',
    ];

    private const SIGNS = [
        'া' => 'a', 'ি' => 'i', 'ী' => 'i', 'ু' => 'u', 'ূ' => 'u', 'ৃ' => 'ri',
        'ে' => 'e', 'ৈ' => 'oi', 'ো' => 'o', 'ৌ' => 'ou',
    ];

    private const MODIFIERS = ['ং' => 'ng', 'ঃ' => 'h', 'ঁ' => ''];

    private const HASANTA = '্';

    public static function toLatin(?string $text): string
    {
        $text = BanglaText::normalize($text);
        if (!BanglaText::hasBangla($text)) {
            return $text;
        }

        $text = str_replace(self::HASANTA . 'য', "\u{E000}", $text);
        $words = preg_split('/[^\p{L}\p{M}\p{N}\x{E000}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', array_map([static::class, 'word'], $words));
    }

    private static function word(string $word): string
    {
        $chars = mb_str_split($word);
        $last = count($chars) - 1;
        $out = '';

        for ($i = 0; $i <= $last; $i++) {
            $ch = $chars[$i];

            if (isset(self::CONSONANTS[$ch])) {
                $out .= self::CONSONANTS[$ch];
                $next = $chars[$i + 1] ?? null;

                if ($next !== null && isset(self::SIGNS[$next])) {
                    $out .= self::SIGNS[$next];
                    $i++;
                } elseif ($next === self::HASANTA) {
                    $i++;
                } elseif ($next !== null && ($i + 1 === $last) && (isset(self::CONSONANTS[$next]) || isset(self::MODIFIERS[$next]))) {
                    // Inherent vowel is spoken before a word-final consonant: "টন" => "ton", "রং" => "rong".
                    $out .= 'o';
                }
                continue;
            }

            $out .= self::VOWELS[$ch] ?? self::SIGNS[$ch] ?? self::MODIFIERS[$ch] ?? ($ch === self::HASANTA ? '' : $ch);
        }

        return $out;
    }

    /**
     * Phonetic key for Latin text: folds sh/s, ph/f, bh/v/b, w/o, ee/i, oo/u, ya-phala and double
     * letters so Banglish spelling variants collapse to one form.
     */
    public static function key(?string $text): string
    {
        $text = BanglaText::normalize($text);
        if (BanglaText::hasBangla($text)) {
            $text = static::toLatin($text);
        }

        $words = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', array_map(function (string $w) {
            if (ctype_digit($w)) {
                return $w;
            }
            $w = strtr($w, ['ee' => 'i', 'oo' => 'u', 'ou' => 'o', 'ph' => 'f', 'chh' => 'ch', 'q' => 'k', 'x' => 'ks', 'z' => 'j']);
            $w = preg_replace('/c(?!h)/', 'k', $w);
            $w = strtr($w, ['sh' => 's', 'kh' => 'k', 'gh' => 'g', 'th' => 't', 'dh' => 'd', 'jh' => 'j', 'bh' => 'b', 'v' => 'b', 'w' => 'o']);
            $w = preg_replace('/([bcdfgjklmnprst])y([aeiou])/', '$1$2', $w);
            $w = preg_replace('/([aeiou])y([aeiou])/', '$1$2', $w);
            $w = preg_replace('/(.)\1+/', '$1', $w);

            return $w;
        }, $words));
    }
}
