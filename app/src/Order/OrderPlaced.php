<?php

namespace App\Order;

final readonly class OrderPlaced
{
    public function __construct(
        public int $productId,
    ) {
    }
}
