<?php

declare(strict_types=1);

namespace Yu\SimilarWords\Enums;

/**
 * How a candidate word relates to the searched phrase, as the AI labelled it; the colour of its point on the map.
 */
enum Relation: string
{
    case Synonym = 'synonym';
    case Association = 'association';
    case Unrelated = 'unrelated';
}
