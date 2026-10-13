<?php

namespace App\Order;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendOrderConfirmation
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrderPlaced $order): void
    {
        // Stands for a call to the email provider.
        usleep(250_000);

        $this->logger->info('order confirmation sent', ['product_id' => $order->productId]);
    }
}
