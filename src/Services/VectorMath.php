<?php

declare(strict_types=1);

namespace Yu\SimilarWords\Services;

/**
 * The maths on embeddings: similarity, a 2D map of many vectors and a short "fingerprint" of one.
 */
class VectorMath
{
    /**
     * Cosine similarity, from −1 (opposite) through 0 (unrelated) to 1 (same direction).
     *
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public function cosine(array $a, array $b): float
    {
        $dot = $normA = $normB = 0.0;

        foreach ($a as $i => $value) {
            $dot += $value * $b[$i];
            $normA += $value * $value;
            $normB += $b[$i] * $b[$i];
        }

        return $normA > 0 && $normB > 0 ? $dot / sqrt($normA * $normB) : 0.0;
    }

    /**
     * Projects the vectors onto their two main directions (PCA) and scales the result into −1…1.
     *
     * Works on the n×n Gram matrix instead of the d×d covariance: with 40 words and 1536 numbers each
     * that is a 41×41 matrix, so plain power iteration is enough.
     *
     * @param  list<list<float>>  $vectors
     * @return list<array{0: float, 1: float}>
     */
    public function project2d(array $vectors): array
    {
        $count = count($vectors);

        if ($count < 3) {
            return array_fill(0, $count, [0.0, 0.0]);
        }

        $centered = $this->centered($vectors);
        $gram = [];

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i; $j < $count; $j++) {
                $gram[$i][$j] = $gram[$j][$i] = $this->dot($centered[$i], $centered[$j]);
            }
        }

        $axes = [];

        foreach ([0, 1] as $axis) {
            [$value, $vector] = $this->topEigen($gram);
            $axes[] = array_map(fn (float $x): float => $x * sqrt(max($value, 0.0)), $vector);

            // Remove the found direction so the next iteration finds the second one
            for ($i = 0; $i < $count; $i++) {
                for ($j = 0; $j < $count; $j++) {
                    $gram[$i][$j] -= $value * $vector[$i] * $vector[$j];
                }
            }
        }

        $scale = max(1e-9, ...array_map(abs(...), [...$axes[0], ...$axes[1]]));

        return array_map(
            fn (int $i): array => [round($axes[0][$i] / $scale, 4), round($axes[1][$i] / $scale, 4)],
            range(0, $count - 1),
        );
    }

    /**
     * The first $length numbers of each vector as whole numbers −9…9, on one scale for all of them,
     * so the fingerprints of two words can be compared by eye.
     *
     * @param  list<list<float>>  $vectors
     * @return list<list<int>>
     */
    public function fingerprints(array $vectors, int $length): array
    {
        $heads = array_map(fn (array $vector): array => array_slice($vector, 0, $length), $vectors);
        $scale = max(1e-9, ...array_map(abs(...), $heads[0]));

        return array_map(
            fn (array $head): array => array_map(fn (float $x): int => (int) max(-9, min(9, round($x / $scale * 9))), $head),
            $heads,
        );
    }

    /**
     * @param  list<list<float>>  $vectors
     * @return list<list<float>>
     */
    private function centered(array $vectors): array
    {
        $count = count($vectors);
        $mean = array_fill(0, count($vectors[0]), 0.0);

        foreach ($vectors as $vector) {
            foreach ($vector as $i => $value) {
                $mean[$i] += $value / $count;
            }
        }

        return array_map(fn (array $vector): array => array_map(fn (float $x, float $m): float => $x - $m, $vector, $mean), $vectors);
    }

    /**
     * The largest eigenvalue of a symmetric matrix and its unit eigenvector; the sign is fixed so that
     * the largest component is positive and the map does not flip between runs.
     *
     * @param  array<int, array<int, float>>  $matrix
     * @return array{0: float, 1: list<float>}
     */
    private function topEigen(array $matrix): array
    {
        $count = count($matrix);
        $vector = array_map(fn (int $i): float => 1.0 + $i / $count, range(0, $count - 1));
        $value = 0.0;

        for ($step = 0; $step < 300; $step++) {
            $next = array_map(fn (array $row): float => $this->dot($row, $vector), $matrix);
            $norm = sqrt($this->dot($next, $next));

            if ($norm < 1e-12) {
                return [0.0, array_fill(0, $count, 0.0)];
            }

            $next = array_map(fn (float $x): float => $x / $norm, $next);
            $converged = abs($norm - $value) < 1e-10 * max(1.0, $norm);
            [$vector, $value] = [$next, $norm];

            if ($converged) {
                break;
            }
        }

        $largest = array_reduce($vector, fn (float $carry, float $x): float => abs($x) > abs($carry) ? $x : $carry, 0.0);

        return [$value, $largest < 0 ? array_map(fn (float $x): float => -$x, $vector) : $vector];
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private function dot(array $a, array $b): float
    {
        $sum = 0.0;

        foreach ($a as $i => $value) {
            $sum += $value * $b[$i];
        }

        return $sum;
    }
}
