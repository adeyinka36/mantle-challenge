<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Checkout\PlaceOrder;
use App\Service\Checkout\PlaceOrderData;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController
{
    #[Route('/checkout', methods: ['POST'])]
    public function checkout(Request $request, PlaceOrder $placeOrder): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || empty($payload['cart'])) {
            return new JsonResponse(['error' => 'Empty cart'], 500);
        }

        $result = $placeOrder(PlaceOrderData::fromPayload($payload));

        return new JsonResponse([
            'id' => $result->orderId,
            'total' => $result->total,
        ]);
    }
}
