<?php
declare(strict_types=1);

namespace App\Service\Checkout;

final class CartLine
{
    public function __construct(
        public readonly string $sku,
        public readonly float $price,
        public readonly int $qty,
    ) {
    }
}
