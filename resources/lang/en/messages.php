<?php

declare(strict_types=1);

return [
    'form' => [
        'label' => 'Word or phrase',
        'button' => 'Find similar',
        'examples' => 'Try:',
    ],
    'examples' => ['coffee', 'cosy coffee shop', 'artificial intelligence', 'website development'],
    'loading' => [
        'candidates' => 'The AI suggests related words and unrelated ones in the language of your phrase',
        'embeddings' => 'Turning every word into a vector of 1536 numbers',
        'map' => 'Measuring the similarity and drawing the map',
    ],
    'show' => 'Show:',
    'relations' => [
        'synonym' => 'synonym',
        'association' => 'association',
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
        'title' => 'Where the vectors agree',
        'sub' => 'The first :count of :total numbers of the embedding, one square per number',
        'yours' => 'your phrase',
        'agree' => 'match',
        'against' => 'differ',
        'score' => 'similarity :score',
        'green' => ':n green',
        'red' => ':n red',
        'hint' => 'A square is green when the number has the same sign in your phrase and in the word: it makes them more similar. Red when the signs differ: the number pulls them apart. The brighter the square, the stronger the effect. Similarity is counted over all the numbers; only part of them is shown here. Point at another word on the map or in the list to compare with it.',
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
