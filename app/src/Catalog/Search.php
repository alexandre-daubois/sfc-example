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

        usort($products, static fn (array $a, array $b): int => similar_text($query, $b['name']) <=> similar_text($query, $a['name']));

        return \array_slice($products, 0, $limit);
    }
}
