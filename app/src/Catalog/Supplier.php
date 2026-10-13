<?php

namespace App\Catalog;

/**
 * Stands for a call to a slow third-party API.
 */
class Supplier
{
    public function fetchPriceFactor(): float
    {
        usleep(300_000);

        return 1 + random_int(-5, 5) / 1000;
    }
}
