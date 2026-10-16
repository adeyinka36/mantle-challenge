<?php
declare(strict_types=1);

namespace App\Service\Checkout;

final class CheckoutMapping
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        private readonly ?PlaceOrderData $checkout,
        public array $errors,
    ) {
    }

    public function checkout(): PlaceOrderData
    {
        if ($this->checkout === null) {
            throw new \LogicException('Cannot place an order that failed validation.');
        }

        return $this->checkout;
    }
}
