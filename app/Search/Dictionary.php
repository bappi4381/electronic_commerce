<?php

namespace App\Search;

/**
 * Synonym groups and stop words from search/*.json, normalised the same way as queries.
 */
class Dictionary
{
    /** @var array<int, string[]> */
    private array $groups = [];

    /** @var array<string, int[]> normalised term => group indexes */
    private array $index = [];

    /** @var array<string, bool> */
    private array $stopWords = [];

    public function __construct(?string $synonymsPath = null, ?string $stopWordsPath = null)
    {
        $synonyms = $this->readJson($synonymsPath ?? config('search.synonyms_path'));
        foreach ($synonyms['groups'] ?? [] as $group) {
            $terms = array_values(array_unique(array_filter(array_map(fn ($t) => implode(' ', BanglaText::words($t)), (array) $group))));
            if (count($terms) < 2) {
                continue;
            }
            $id = count($this->groups);
            $this->groups[$id] = $terms;
            foreach ($terms as $term) {
                $this->index[$term][] = $id;
            }
        }

        foreach ($this->readJson($stopWordsPath ?? config('search.stopwords_path'))['words'] ?? [] as $word) {
            $this->stopWords[BanglaText::normalize($word)] = true;
        }
    }

    public function groups(): array
    {
        return $this->groups;
    }

    public function stopWords(): array
    {
        return array_keys($this->stopWords);
    }

    public function isStopWord(string $word): bool
    {
        return isset($this->stopWords[$word]);
    }

    /** Words of a query with stop words removed (all of them kept if nothing else is left). */
    public function contentWords(string $text): array
    {
        $words = BanglaText::words($text);
        $kept = array_values(array_filter($words, fn ($w) => !$this->isStopWord($w)));

        return $kept ?: $words;
    }

    /**
     * Alternative phrasings of a query: each synonym term found as whole words is swapped for the
     * other members of its group ("ricecooker" => "rice cooker", "রাইস কুকার", …).
     *
     * @return string[]
     */
    public function expand(string $query, int $limit = 12): array
    {
        $words = $this->contentWords($query);
        $count = count($words);
        $results = [];

        for ($len = min(4, $count); $len >= 1; $len--) {
            for ($start = 0; $start + $len <= $count; $start++) {
                $phrase = implode(' ', array_slice($words, $start, $len));
                foreach ($this->index[$phrase] ?? [] as $groupId) {
                    foreach ($this->groups[$groupId] as $alt) {
                        if ($alt === $phrase) {
                            continue;
                        }
                        $variant = implode(' ', array_merge(array_slice($words, 0, $start), [$alt], array_slice($words, $start + $len)));
                        $results[$variant] = true;
                        if (count($results) >= $limit) {
                            return array_keys($results);
                        }
                    }
                }
            }
        }

        return array_keys($results);
    }

    /** Bangla members of every group whose Latin term appears in the text (brand/term dictionary). */
    public function banglaFor(string $text): array
    {
        $words = BanglaText::words($text);
        $found = [];
        $count = count($words);

        for ($len = min(3, $count); $len >= 1; $len--) {
            for ($start = 0; $start + $len <= $count; $start++) {
                $phrase = implode(' ', array_slice($words, $start, $len));
                if (BanglaText::hasBangla($phrase)) {
                    continue;
                }
                foreach ($this->index[$phrase] ?? [] as $groupId) {
                    foreach ($this->groups[$groupId] as $term) {
                        if (BanglaText::hasBangla($term)) {
                            $found[$term] = true;
                        }
                    }
                }
            }
        }

        return array_keys($found);
    }

    /** Meilisearch "synonyms" setting: every term maps to all other terms of its groups. */
    public function forMeilisearch(): array
    {
        $map = [];
        foreach ($this->groups as $terms) {
            foreach ($terms as $term) {
                $map[$term] = array_values(array_unique(array_merge($map[$term] ?? [], array_values(array_diff($terms, [$term])))));
            }
        }

        return $map;
    }

    private function readJson(?string $path): array
    {
        if (!$path || !is_file($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }
}
