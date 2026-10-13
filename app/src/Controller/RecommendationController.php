<?php

namespace App\Controller;

use App\Recommendation\RecommendationEngine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class RecommendationController extends AbstractController
{
    #[Route('/recommendations', methods: ['GET'])]
    public function __invoke(RecommendationEngine $engine, #[MapQueryParameter] string $visitor = 'anonymous'): JsonResponse
    {
        return $this->json(['products' => $engine->forVisitor($visitor)['products']]);
    }
}
