<?php

declare(strict_types=1);

namespace Yu\SimilarWords\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A visitor compared two phrases: fired for every finished comparison, from the cache too, and for failed ones.
 */
class SimilarWordsComparedEvent
{
    use Dispatchable;

    /**
     * @param  string  $first  the first phrase, trimmed and with joined whitespace
     * @param  string  $second  the second phrase, trimmed and with joined whitespace
     * @param  string  $locale  language of the tool for this visitor
     * @param  float|null  $similarity  SimilarWordsFinder::compare(); null when the comparison failed
     * @param  string|null  $error  reason of the failure, e.g. "timeout" or "rate_limited"; null on success
     * @param  bool  $fromCache  the similarity was taken from the cache, no AI request
     */
    public function __construct(
        public readonly string $first,
        public readonly string $second,
        public readonly string $locale,
        public readonly ?float $similarity = null,
        public readonly ?string $error = null,
        public readonly bool $fromCache = false,
    ) {}
}
