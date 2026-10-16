<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Checkout\PlaceOrder;
use App\Service\Checkout\PlaceOrderData;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController
{
    #[Route('/checkout', methods: ['POST'])]
    public function checkout(
        #[MapRequestPayload] PlaceOrderData $checkout,
        PlaceOrder $placeOrder,
    ): JsonResponse {
        $result = $placeOrder($checkout);

        return new JsonResponse([
            'id' => $result->orderId,
            'total' => $result->total,
        ]);
    }
}
