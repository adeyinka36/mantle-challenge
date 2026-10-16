<?php
declare(strict_types=1);

namespace App\Service\Checkout;

final class PlaceOrderData
{
    /**
     * @param list<CartLine> $cart
     */
    public function __construct(
        public string $email,
        public string $customerType,
        public array $cart,
        public bool $gift,
        public ?string $recipientEmail,
        public bool $sample,
        public ?string $mobile = null,
    ) {
    }
}
