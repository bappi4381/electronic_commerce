<?php

namespace Tests\Unit;

use App\Search\BanglaText;
use App\Search\Transliterator;
use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BanglaTextTest extends TestCase
{
    public function test_normalize_lowercases_collapses_spaces_and_strips_zero_width_joiners(): void
    {
        $this->assertSame('t-shirt slim', BanglaText::normalize("  T-Shirt \u{200C} \t SLIM "));
        $this->assertSame('ক্ষ', BanglaText::normalize("ক্\u{200D}ষ"));
    }

    public function test_composed_and_decomposed_nukta_letters_are_equal(): void
    {
        // ড় as one code point vs ড + nukta.
        $this->assertSame(BanglaText::normalize("\u{09DC}"), BanglaText::normalize("\u{09A1}\u{09BC}"));
        $this->assertSame(BanglaText::normalize("ও\u{09DF}ালটন"), BanglaText::normalize("ও\u{09AF}\u{09BC}ালটন"));
    }

    public function test_bangla_digits_become_ascii(): void
    {
        $this->assertSame('wrc 18', BanglaText::normalize('WRC ১৮'));
    }

    public function test_compact_removes_spaces_hyphens_and_punctuation(): void
    {
        $this->assertSame('tshirt', BanglaText::compact('T-Shirt'));
        $this->assertSame('tshirt', BanglaText::compact('t shirt'));
        $this->assertSame('টিশার্ট', BanglaText::compact('টি-শার্ট'));
    }

    public function test_script_detection(): void
    {
        $this->assertSame(BanglaText::SCRIPT_BANGLA, BanglaText::script('কেটলি'));
        $this->assertSame(BanglaText::SCRIPT_LATIN, BanglaText::script('kettle'));
        $this->assertSame(BanglaText::SCRIPT_MIXED, BanglaText::script('walton কেটলি'));
        $this->assertSame(BanglaText::SCRIPT_OTHER, BanglaText::script('फ'));
    }

    public function test_distance_counts_an_adjacent_swap_as_one_typo(): void
    {
        $this->assertSame(1, BanglaText::distance('kettel', 'kettle'));
        $this->assertSame(1, BanglaText::distance('wolton', 'walton'));
        $this->assertSame(1, BanglaText::distance('কেটলী', 'কেটলি'));
    }

    #[DataProvider('banglish')]
    public function test_transliterates_bangla_to_banglish(string $bangla, string $latin): void
    {
        $this->assertSame($latin, Transliterator::toLatin($bangla));
    }

    public static function banglish(): array
    {
        return [
            ['কেটলি', 'ketli'],
            ['টি-শার্ট', 'ti shart'],
            ['রাইস কুকার', 'rais kukar'],
            ['ওয়ালটন', 'oyalton'],
            ['ফুটবল', 'futbol'],
            ['ইলেকট্রিক', 'ilektrik'],
            ['ল্যাপটপ', 'lyaptop'],
        ];
    }

    public function test_phonetic_key_folds_banglish_spelling_variants(): void
    {
        $this->assertSame(Transliterator::key('walton'), Transliterator::key('ওয়ালটন'));
        $this->assertSame(Transliterator::key('laptop'), Transliterator::key('ল্যাপটপ'));
        $this->assertSame(Transliterator::key('ti shart'), Transliterator::key('টি শার্ট'));
    }

    public function test_discount_percent(): void
    {
        $this->assertSame(15, Money::discountPercent(2270, 1930));
        $this->assertSame(0, Money::discountPercent(500, 500));
        $this->assertSame(0, Money::discountPercent(0, 100));
    }
}
