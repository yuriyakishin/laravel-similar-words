<?php

declare(strict_types=1);

namespace Yu\SimilarWords;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Yu\SimilarWords\Livewire\SimilarWordsComponent;
use Yu\SimilarWords\Services\OpenAiClient;

class SimilarWordsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/similar-words.php', 'similar-words');

        $this->app->bind(OpenAiClient::class, fn (): OpenAiClient => new OpenAiClient(config('similar-words.openai')));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'similar-words');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'similar-words');

        // The name is required: without it Livewire 4 makes up a hash and <livewire:similar-words /> is not found
        Livewire::component('similar-words', SimilarWordsComponent::class);
    }
}
