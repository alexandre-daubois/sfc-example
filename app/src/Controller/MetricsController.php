<?php

namespace App\Controller;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController
{
    #[Route('/metrics', methods: ['GET'], condition: 'request.getPort() == 6060')]
    public function __invoke(
        CollectorRegistry $registry,
        #[Autowire(service: 'messenger.transport.async')] MessageCountAwareInterface $async,
    ): Response {
        $registry->getOrRegisterGauge('shop', 'messenger_queue_messages', 'Messages waiting in the async transport.')
            ->set($async->getMessageCount());

        return new Response(
            (new RenderTextFormat())->render($registry->getMetricFamilySamples()),
            headers: ['Content-Type' => RenderTextFormat::MIME_TYPE],
        );
    }
}
