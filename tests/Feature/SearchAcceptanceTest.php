<?php

namespace Tests\Feature;

use App\Models\SearchLog;
use App\Search\Engines\DatabaseEngine;
use App\Search\SearchService;
use Database\Seeders\SearchDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Acceptance queries from the search spec. They run on the engine selected by SEARCH_TEST_ENGINE
 * (database by default; set it to "meilisearch" with a running server and an imported index).
 */
class SearchAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'search.engine' => env('SEARCH_TEST_ENGINE', 'database'),
            'search.cache_ttl' => 0,
        ]);
        DatabaseEngine::flush();
        SearchService::flushLookups();
        $this->seed(SearchDemoSeeder::class);
    }

    #[DataProvider('queries')]
    public function test_query_finds_relevant_products(string $query, string $expected): void
    {
        $response = $this->getJson('/api/search?' . http_build_query(['q' => $query, 'lang' => 'en']));

        $response->assertOk()->assertJsonStructure([
            'query', 'normalized', 'script', 'products' => [['id', 'name', 'name_html', 'brand', 'url', 'image', 'price', 'old_price', 'discount', 'in_stock']],
            'categories', 'brands', 'did_you_mean', 'total', 'see_all_url',
        ]);

        $names = collect($response->json('products'))->pluck('name')->implode(' | ');
        $this->assertNotSame('', $names, "No products for \"{$query}\"");
        $this->assertStringContainsStringIgnoringCase($expected, $names, "\"{$query}\" returned: {$names}");
    }

    public static function queries(): array
    {
        return [
            'tshirt' => ['tshirt', 'T-Shirt'],
            't shirt' => ['t shirt', 'T-Shirt'],
            't-shirt' => ['t-shirt', 'T-Shirt'],
            'টি শার্ট' => ['টি শার্ট', 'T-Shirt'],
            'টিশার্ট' => ['টিশার্ট', 'T-Shirt'],
            'ti shirt' => ['ti shirt', 'T-Shirt'],
            'kettel (typo)' => ['kettel', 'Kettle'],
            'ketli (Banglish)' => ['ketli', 'Kettle'],
            'কেটলি' => ['কেটলি', 'Kettle'],
            'rice cooker' => ['rice cooker', 'Rice Cooker'],
            'ricecooker' => ['ricecooker', 'Rice Cooker'],
            'রাইস কুকার' => ['রাইস কুকার', 'Rice Cooker'],
            'wolton (typo)' => ['wolton', 'Walton'],
            'ওয়ালটন' => ['ওয়ালটন', 'Walton'],
            'pendrive' => ['pendrive', 'Pen Drive'],
            'পেন ড্রাইভ' => ['পেন ড্রাইভ', 'Pen Drive'],
        ];
    }

    public function test_typo_suggests_a_correction(): void
    {
        $this->getJson('/api/search?q=kettel')->assertOk()->assertJsonPath('did_you_mean', 'kettle');
    }

    public function test_unsupported_script_returns_empty_results_without_error(): void
    {
        $this->getJson('/api/search?q=' . urlencode('फ'))
            ->assertOk()
            ->assertJsonPath('products', [])
            ->assertJsonPath('total', 0);
    }

    public function test_too_short_latin_query_returns_nothing_but_one_bangla_letter_is_searched(): void
    {
        $this->getJson('/api/search?q=k')->assertOk()->assertJsonPath('products', []);
        $this->assertNotEmpty($this->getJson('/api/search?q=' . urlencode('কে'))->json('products'));
    }

    public function test_highlights_are_escaped_and_marked(): void
    {
        $html = $this->getJson('/api/search?q=kettle')->json('products.0.name_html');

        $this->assertStringContainsString('<mark>Kettle</mark>', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_suggestions_include_matching_categories_and_brands(): void
    {
        $json = $this->getJson('/api/search?q=walton')->json();

        $this->assertContains('Walton', array_column($json['brands'], 'name'));
        $this->assertNotEmpty($json['categories']);
    }

    public function test_zero_result_queries_are_logged_with_popular_fallbacks(): void
    {
        $json = $this->getJson('/api/search?q=qwertyzzz')->assertOk()->json();

        $this->assertSame(0, $json['total']);
        $this->assertNotEmpty($json['popular']);
        $this->assertNotEmpty($json['trending']);
        $this->assertDatabaseHas('search_logs', ['normalized' => 'qwertyzzz', 'results_count' => 0]);
    }

    public function test_results_page_filters_sorts_and_logs(): void
    {
        $this->get('/en/search?q=kettle&sort=price_asc')
            ->assertOk()
            ->assertSee('Walton Electric Kettle')
            ->assertSee('Philips Electric Kettle');

        $this->get('/en/search?q=kettle&in_stock=1&max_price=1700')
            ->assertOk()
            ->assertSee('Walton Electric Kettle')
            ->assertDontSee('Philips Electric Kettle - HD9350');

        $this->assertTrue(SearchLog::where('normalized', 'kettle')->where('source', 'results')->exists());
    }

    public function test_bangla_results_page_renders(): void
    {
        $this->get('/bn/search?q=' . urlencode('রাইস কুকার'))->assertOk()->assertSee('রাইস কুকার');
    }

    public function test_api_is_rate_limited(): void
    {
        config(['search.rate_limit' => 3]);

        foreach (range(1, 3) as $i) {
            $this->getJson('/api/search?q=kettle' . $i)->assertOk();
        }
        $this->getJson('/api/search?q=kettle')->assertStatus(429);
    }

    public function test_p95_latency_is_under_150ms(): void
    {
        $queries = array_column(static::queries(), 0);
        $this->getJson('/api/search?q=warmup'); // builds the in-memory index

        $timings = [];
        foreach (array_merge($queries, $queries) as $query) {
            $start = hrtime(true);
            $this->getJson('/api/search?' . http_build_query(['q' => $query]))->assertOk();
            $timings[] = (hrtime(true) - $start) / 1e6;
        }

        sort($timings);
        $p95 = $timings[(int) ceil(count($timings) * 0.95) - 1];
        $this->assertLessThan(150, $p95, sprintf('p95 %.1f ms (median %.1f ms)', $p95, $timings[intdiv(count($timings), 2)]));
    }
}
