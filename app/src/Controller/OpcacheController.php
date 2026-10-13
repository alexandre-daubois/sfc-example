<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class OpcacheController extends AbstractController
{
    #[Route('/opcache', methods: ['GET'], condition: 'request.getPort() == 6060')]
    public function __invoke(): JsonResponse
    {
        $status = opcache_get_status(false);

        return (new JsonResponse([
            'cache_full' => $status['cache_full'],
            'cached_scripts' => $status['opcache_statistics']['num_cached_scripts'],
            'max_cached_keys' => $status['opcache_statistics']['max_cached_keys'],
            'hit_rate' => round($status['opcache_statistics']['opcache_hit_rate'], 2),
            'misses' => $status['opcache_statistics']['misses'],
            'oom_restarts' => $status['opcache_statistics']['oom_restarts'],
            'hash_restarts' => $status['opcache_statistics']['hash_restarts'],
        ]))->setEncodingOptions(\JSON_PRETTY_PRINT);
    }
}
