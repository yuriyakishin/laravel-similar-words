<?php

declare(strict_types=1);

return [
    'enabled' => env('SIMILAR_WORDS_ENABLED', true),

    'openai' => [
        'api_key' => env('SIMILAR_WORDS_OPENAI_API_KEY'),
        'base_url' => env('SIMILAR_WORDS_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        /* Suggests the candidate words; their order and scores come from the embeddings, not from this model. */
        'chat_model' => env('SIMILAR_WORDS_CHAT_MODEL', 'gpt-4o-mini'),
        'embedding_model' => env('SIMILAR_WORDS_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'timeout' => (int) env('SIMILAR_WORDS_OPENAI_TIMEOUT', 25),
    ],

    /*
     * Words and phrases the AI suggests for one search, by relation to the phrase. The AI is asked for each group
     * separately, and a longer answer is cut to these numbers: the unrelated words are only a scale, not the result.
     */
    'candidates' => [
        'synonym' => 10,
        'association' => 22,
        'unrelated' => 8,
    ],

    /* Numbers of the vector shown as its "fingerprint": the first ones carry the most meaning in text-embedding-3. */
    'fingerprint' => 192,

    'max_length' => 80,

    'rate_limit' => [
        /* Searches per visitor IP that reach the AI; a phrase searched before comes from the cache and does not count. */
        'per_hour' => (int) env('SIMILAR_WORDS_PER_HOUR', 10),
        /* Comparisons of two phrases per visitor IP: only two embeddings each, so the limit is higher. */
        'compare_per_hour' => (int) env('SIMILAR_WORDS_COMPARE_PER_HOUR', 40),
    ],

    /* Embeddings of the same text do not change, so results are kept for a week. */
    'cache' => [
        'ttl' => 7 * 86400,
    ],
];
