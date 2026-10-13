<?php

namespace App\Controller;

use App\Order\OrderPlaced;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/orders', methods: ['POST'])]
    public function __invoke(MessageBusInterface $bus): Response
    {
        $bus->dispatch(new OrderPlaced(random_int(1, 2000)));

        return new Response(status: Response::HTTP_ACCEPTED);
    }
}
