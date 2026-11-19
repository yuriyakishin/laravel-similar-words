<?php

declare(strict_types=1);

namespace Yu\SimilarWords\Livewire;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;
use Yu\SimilarWords\Services\SimilarWordsFinder;
use Yu\WebToolsCore\Exceptions\ToolException;
use Yu\WebToolsCore\Locale\LocaleResolver;

/**
 * The similar words tool: a phrase in, the nearest words by embedding out, plus a comparison of two phrases.
 */
class SimilarWordsComponent extends Component
{
    public const NAME = 'similar-words';

    public string $phrase = '';

    public string $first = '';

    public string $second = '';

    /** Language of the form and the texts. */
    #[Locked]
    public string $locale = 'en';

    /** SimilarWordsFinder::find(); null until a search succeeds. */
    #[Locked]
    public ?array $report = null;

    #[Locked]
    public ?string $error = null;

    /** Similarity of the two compared phrases; null until a comparison succeeds. */
    #[Locked]
    public ?float $similarity = null;

    #[Locked]
    public ?string $compareError = null;

    public function mount(): void
    {
        // Here request() is the visitor's page request; Livewire AJAX requests do not carry the page's language
        $locales = new LocaleResolver(dirname(__DIR__, 2).'/resources/lang');
        $this->locale = $locales->fromBrowser(request()) ?? $locales->toSupported(app()->getLocale()) ?? 'en';
    }

    /**
     * A search started from an example chip or by clicking a word on the map.
     */
    public function searchFor(string $phrase, SimilarWordsFinder $finder): void
    {
        $this->phrase = $phrase;
        $this->search($finder);
    }

    public function search(SimilarWordsFinder $finder): void
    {
        $this->reset('report', 'error');
        $this->phrase = $this->clean($this->phrase);
        $this->validateOnly('phrase', ...$this->rules('phrase'));

        $this->report = $this->attempt('error', $finder, fn (): array => Cache::remember(
            self::NAME.':'.sha1(mb_strtolower($this->phrase)).':'.$this->locale,
            (int) config('similar-words.cache.ttl'),
            function () use ($finder): array {
                $this->hitRateLimit('search', (int) config('similar-words.rate_limit.per_hour'));

                return $finder->find($this->phrase, $this->locale);
            },
        ));

        if ($this->report !== null && $this->first === '' && $this->second === '') {
            $this->first = $this->report['phrase'];
            $this->second = $this->report['items'][0]['text'] ?? '';
        }
    }

    public function compare(SimilarWordsFinder $finder): void
    {
        $this->reset('similarity', 'compareError');
        $this->first = $this->clean($this->first);
        $this->second = $this->clean($this->second);
        $this->validateOnly('first', ...$this->rules('first'));
        $this->validateOnly('second', ...$this->rules('second'));

        $pair = [mb_strtolower($this->first), mb_strtolower($this->second)];
        sort($pair);

        $this->similarity = $this->attempt('compareError', $finder, fn (): float => Cache::remember(
            self::NAME.':pair:'.sha1(implode("\n", $pair)),
            (int) config('similar-words.cache.ttl'),
            function () use ($finder): float {
                $this->hitRateLimit('compare', (int) config('similar-words.rate_limit.compare_per_hour'));

                return $finder->compare($this->first, $this->second);
            },
        ));
    }

    public function render(): View
    {
        return view('similar-words::similar-words');
    }

    /**
     * Runs a request to the AI and turns its failures into a translated message in $errorProperty.
     */
    private function attempt(string $errorProperty, SimilarWordsFinder $finder, Closure $request): mixed
    {
        if (!config('similar-words.enabled') || !$finder->isConfigured()) {
            $this->{$errorProperty} = $this->message('errors.disabled');

            return null;
        }

        try {
            return $request();
        } catch (ToolException $e) {
            $this->{$errorProperty} = $this->message('errors.'.$e->reason, $e->replace);
        } catch (Throwable $e) {
            report($e);
            $this->{$errorProperty} = $this->message('errors.generic');
        }

        return null;
    }

    /**
     * @return array{0: array<string, list<string>>, 1: array<string, string>}
     */
    private function rules(string $field): array
    {
        return [
            [$field => ['required', 'string', 'max:'.config('similar-words.max_length')]],
            [$field.'.*' => $this->message('errors.invalid_phrase', ['max' => config('similar-words.max_length')])],
        ];
    }

    /**
     * Trims and joins whitespace: "  cosy   café " and "cosy café" are one search and one cache entry.
     */
    private function clean(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * A text of the tool in the visitor's language; falls back to the shared core texts.
     *
     * @param  array<string, string|int>  $replace
     */
    private function message(string $key, array $replace = []): string
    {
        $own = self::NAME.'::messages.'.$key;

        return __(Lang::has($own, $this->locale) ? $own : 'web-tools-core::messages.'.$key, $replace, $this->locale);
    }

    /**
     * Counts one request to the AI per visitor IP, failed ones too.
     */
    private function hitRateLimit(string $name, int $perHour): void
    {
        $key = self::NAME.':'.$name.':'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, $perHour)) {
            throw new ToolException('rate_limited', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]);
        }

        RateLimiter::hit($key, 3600);
    }
}
