<?php

namespace App\Catalog;

class Search
{
    public function __construct(
        private readonly Catalog $catalog,
    ) {
    }

    /**
     * Ranks every product by how close its name is to the query.
     *
     * @return list<array{id: int, name: string}>
     */
    public function rank(string $query, int $limit): array
    {
        $products = $this->catalog->names();

        $scores = array_map(static fn (array $product): int => similar_text($query, $product['name']), $products);
        arsort($scores);

        return array_map(static fn (int $i): array => $products[$i], array_keys(\array_slice($scores, 0, $limit, true)));
    }
}
