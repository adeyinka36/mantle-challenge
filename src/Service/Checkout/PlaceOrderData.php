<?php
declare(strict_types=1);

namespace App\Service\Checkout;

final class PlaceOrderData
{
    /**
     * @param list<array{sku: string, price: float|int, qty: int}> $cart
     */
    public function __construct(
        public string $email,
        public ?string $customerType,
        public array $cart,
        public bool $gift,
        public ?string $recipientEmail,
        public bool $sample,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            email: $payload['email'],
            customerType: $payload['customerType'] ?? null,
            cart: $payload['cart'],
            gift: !empty($payload['gift']),
            recipientEmail: $payload['recipientEmail'] ?? null,
            sample: !empty($payload['sample']),
        );
    }
}
