<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:seed', 'Fill the catalog with 2,000 products and their reviews, once')]
class SeedCommand
{
    private const ADJECTIVES = ['Red', 'Wooden', 'Vintage', 'Smart', 'Tiny', 'Solar', 'Velvet', 'Steel'];
    private const NOUNS = ['Lamp', 'Chair', 'Kettle', 'Backpack', 'Clock', 'Speaker', 'Mug', 'Bike'];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        if ($this->connection->fetchOne('SELECT COUNT(*) FROM product') > 0) {
            return 0;
        }

        $this->connection->transactional(function (Connection $connection): void {
            for ($i = 1; $i <= 2000; ++$i) {
                $name = \sprintf('%s %s %d', self::ADJECTIVES[$i % 8], self::NOUNS[intdiv($i, 8) % 8], $i);
                $connection->insert('product', ['id' => $i, 'name' => $name, 'price' => random_int(100, 10_000) / 100]);
            }

            $connection->executeStatement('INSERT INTO review (product_id, rating) SELECT p.id, 1 + floor(random() * 5) FROM product p, generate_series(1, 10)');
            $connection->executeStatement("SELECT setval(pg_get_serial_sequence('product', 'id'), 2000)");
        });

        $io->success('Catalog seeded: 2,000 products, 20,000 reviews.');

        return 0;
    }
}
