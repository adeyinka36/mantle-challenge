<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Checkout\CheckoutMapper;
use App\Service\Checkout\PlaceOrder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController
{
    #[Route('/checkout', methods: ['POST'])]
    public function checkout(Request $request, PlaceOrder $placeOrder, CheckoutMapper $mapper): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $mapped = $mapper->map($payload);
        if ($mapped->errors !== []) {
            return new JsonResponse(['errors' => $mapped->errors], 422);
        }

        $result = $placeOrder($mapped->checkout());

        return new JsonResponse([
            'id' => $result->orderId,
            'total' => $result->total,
        ]);
    }
}
