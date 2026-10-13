<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return list<array{id: int, name: string, price: float, rating: ?float}>
     */
    public function findPageWithRating(int $limit): array
    {
        $products = $this->createQueryBuilder('p')
            ->select('p.id', 'p.name', 'p.price', 'AVG(r.rating) AS rating')
            ->leftJoin('p.reviews', 'r')
            ->groupBy('p.id')
            ->orderBy('p.id')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        foreach ($products as &$product) {
            $product['rating'] = null === $product['rating'] ? null : round((float) $product['rating'], 2);
        }

        return $products;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function findAllNames(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id', 'p.name')
            ->getQuery()
            ->getArrayResult();
    }

    public function applyPriceFactor(float $factor): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.price', 'p.price * :factor')
            ->setParameter('factor', $factor)
            ->getQuery()
            ->execute();
    }
}
