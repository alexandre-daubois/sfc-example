<?php

namespace App\Controller;

use App\Order\OrderPlaced;
use Prometheus\CollectorRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/orders', methods: ['POST'])]
    public function __invoke(MessageBusInterface $bus, CollectorRegistry $registry): Response
    {
        $bus->dispatch(new OrderPlaced(random_int(1, 2000)));
        $registry->getOrRegisterCounter('shop', 'orders_placed_total', 'Orders placed.')->inc();

        return new Response(status: Response::HTTP_ACCEPTED);
    }
}
