<?php

namespace App\Recommendation;

class RecommendationEngine
{
    /**
     * Memoized per visitor, so a visitor keeps seeing the same recommendations.
     *
     * @var array<string, array{products: list<int>, scores: list<float>}>
     */
    private array $byVisitor = [];

    /**
     * @return array{products: list<int>, scores: list<float>}
     */
    public function forVisitor(string $visitor): array
    {
        return $this->byVisitor[$visitor] ??= $this->compute();
    }

    /**
     * Stands for a call to a recommendation model.
     *
     * @return array{products: list<int>, scores: list<float>}
     */
    private function compute(): array
    {
        $scores = [];
        for ($i = 0; $i < 4096; ++$i) {
            $scores[] = mt_rand() / mt_getrandmax();
        }

        return ['products' => [random_int(1, 2000), random_int(1, 2000), random_int(1, 2000)], 'scores' => $scores];
    }
}
