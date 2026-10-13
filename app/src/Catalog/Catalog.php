<?php

namespace App\Catalog;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\Lock\LockFactory;

/**
 * Readers take a shared lock so they never see a half-updated catalog.
 */
class Catalog
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly Supplier $supplier,
        private readonly LockFactory $locks,
    ) {
    }

    /**
     * @return list<Product>
     */
    public function list(int $limit): array
    {
        return $this->read(fn () => $this->products->findBy([], ['id' => 'ASC'], $limit));
    }

    public function get(int $id): ?Product
    {
        return $this->read(fn () => $this->products->find($id));
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function names(): array
    {
        return $this->read(fn () => $this->products->findAllNames());
    }

    public function refresh(): void
    {
        $lock = $this->locks->createLock('catalog');
        $lock->acquire(true);

        try {
            $this->products->applyPriceFactor($this->supplier->fetchPriceFactor());
        } finally {
            $lock->release();
        }
    }

    /**
     * @template T
     *
     * @param callable(): T $read
     *
     * @return T
     */
    private function read(callable $read): mixed
    {
        $lock = $this->locks->createLock('catalog');
        $lock->acquireRead(true);

        try {
            return $read();
        } finally {
            $lock->release();
        }
    }
}
