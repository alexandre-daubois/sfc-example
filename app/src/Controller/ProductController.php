<?php

namespace App\Controller;

use App\Catalog\Catalog;
use App\Catalog\Search;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    public function __construct(
        private readonly Catalog $catalog,
    ) {
    }

    #[Route('/products', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->catalog->list(100));
    }

    #[Route('/products/{id<\d+>}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->catalog->get($id);
        if (null === $product) {
            throw $this->createNotFoundException();
        }

        return $this->json([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
        ]);
    }

    #[Route('/search', methods: ['GET'])]
    public function search(Search $search, #[MapQueryParameter] string $q = 'lamp'): JsonResponse
    {
        return $this->json($search->rank($q, 20));
    }

    /**
     * The "sync now" button of the back office: it reloads the prices from the supplier.
     */
    #[Route('/sync', methods: ['GET'])]
    public function sync(): Response
    {
        $this->catalog->refresh();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
