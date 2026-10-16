<?php
declare(strict_types=1);

namespace App\Service\Checkout;

final class PlaceOrderResult
{
    public function __construct(
        public ?int $orderId,
        public float $total,
    ) {
    }
}
