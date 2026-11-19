<?php

declare(strict_types=1);

return [
    'form' => [
        'label' => 'Word or phrase',
        'button' => 'Find similar',
        'hint' => 'Free · no sign-up · up to :count searches per hour · any language',
        'examples' => 'Try:',
    ],
    'examples' => ['coffee', 'cosy coffee shop', 'artificial intelligence', 'website development'],
    'loading' => [
        'candidates' => 'The AI suggests related words, translations and unrelated ones',
        'embeddings' => 'Turning every word into a vector of 1536 numbers',
        'map' => 'Measuring the similarity and drawing the map',
    ],
    'relations' => [
        'synonym' => 'synonym',
        'association' => 'association',
        'translation' => 'other language',
        'unrelated' => 'unrelated',
    ],
    'map' => [
        'title' => 'Map of meanings',
        'label' => 'Map of similar words',
        'radar' => 'Radar',
        'plane' => '2D map',
        'radar_sub' => 'Closer to the centre = more similar',
        'plane_sub' => 'Vectors squeezed to 2 dimensions (PCA): close together = similar meaning',
        'click' => 'Click a word to search for it',
    ],
    'rank' => [
        'title' => 'Nearest in meaning',
        'sub' => 'Cosine similarity of the vectors, 0–1',
    ],
    'print' => [
        'title' => 'Vector fingerprint',
        'sub' => 'The first :count of :total numbers the embedding is made of',
        'yours' => 'your phrase',
        'score' => 'similarity :score',
        'hint' => 'Point at a word on the map or in the list: the higher the similarity, the more the colour patterns match.',
    ],
    'compare' => [
        'title' => 'Compare two phrases',
        'sub' => 'How close two meanings are',
        'first' => 'First phrase',
        'second' => 'Second phrase',
        'button' => 'Compare',
        'none' => 'unrelated',
        'related' => 'related',
        'same' => 'the same',
        'verdicts' => [
            'same' => 'Almost the same meaning',
            'close' => 'Very close',
            'related' => 'Related topics',
            'weak' => 'A weak link',
            'unrelated' => 'Almost unrelated',
        ],
    ],
    'model' => 'Vectors: :model, :dimensions numbers each',
    'errors' => [
        'invalid_phrase' => 'Enter a word or phrase of up to :max characters',
        'ai_unavailable' => 'The AI service did not answer. Please try again in a minute',
        'ai_bad_response' => 'The AI gave an answer that could not be read. Please try again',
        'rate_limited' => 'Too many searches. Try again in :minutes min',
    ],
];
