<?php

declare(strict_types=1);

namespace Yu\SimilarWords\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A visitor searched for words similar to a phrase: fired for every finished search, from the cache too, and for failed ones.
 */
class SimilarWordsEvent
{
    use Dispatchable;

    /**
     * @param  string  $phrase  the searched phrase, trimmed and with joined whitespace
     * @param  string  $locale  language of the tool for this visitor
     * @param  array<string, mixed>|null  $report  SimilarWordsFinder::find(); null when the search failed
     * @param  string|null  $error  reason of the failure, e.g. "timeout" or "rate_limited"; null on success
     * @param  bool  $fromCache  the report was taken from the cache, no AI request
     */
    public function __construct(
        public readonly string $phrase,
        public readonly string $locale,
        public readonly ?array $report = null,
        public readonly ?string $error = null,
        public readonly bool $fromCache = false,
    ) {}
}
