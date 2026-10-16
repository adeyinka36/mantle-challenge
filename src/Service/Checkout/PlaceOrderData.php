<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlaceOrderData
{
    public const CUSTOMER_TYPES = ['regular', 'vip', 'staff', 'trade'];

    /**
     * @param list<array{sku: string, price: float|int, qty: int}> $cart
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required.', normalizer: 'trim')]
        #[Assert\Email(message: 'Email is not valid.')]
        public string $email,

        #[Assert\Choice(choices: self::CUSTOMER_TYPES, message: 'Customer type is not supported.')]
        public string $customerType = 'regular',

        #[Assert\Type('array', message: 'Cart must be a list of items.')]
        #[Assert\Count(min: 1, minMessage: 'Cart should not be empty.')]
        #[Assert\All([
            new Assert\Collection(
                fields: [
                    'sku' => new Assert\Required([
                        new Assert\NotBlank(message: 'Sku is required.'),
                        new Assert\Type('string', message: 'Sku must be a string.'),
                    ]),
                    'price' => new Assert\Required([
                        new Assert\NotNull(message: 'Price is required.'),
                        new Assert\Type(['int', 'float'], message: 'Price must be a number.'),
                        new Assert\PositiveOrZero(message: 'Price must be zero or greater.'),
                    ]),
                    'qty' => new Assert\Required([
                        new Assert\NotNull(message: 'Quantity is required.'),
                        new Assert\Type('int', message: 'Quantity must be a whole number.'),
                        new Assert\Positive(message: 'Quantity must be at least 1.'),
                    ]),
                ],
                allowExtraFields: false,
                allowMissingFields: false,
            ),
        ])]
        public array $cart = [],

        #[Assert\Type('bool', message: 'Gift must be true or false.')]
        public bool $gift = false,

        #[Assert\When(
            expression: 'this.gift === true',
            constraints: [
                new Assert\NotBlank(message: "A gift needs the recipient's email."),
            ],
        )]
        #[Assert\Email(message: 'Recipient email is not valid.')]
        public ?string $recipientEmail = null,

        #[Assert\Type('bool', message: 'Sample must be true or false.')]
        public bool $sample = false,

        #[Assert\Type('string', message: 'Mobile must be a string.')]
        public ?string $mobile = null,
    ) {
    }
}
