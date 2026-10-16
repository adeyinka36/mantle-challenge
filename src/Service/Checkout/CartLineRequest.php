<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use Symfony\Component\Validator\Constraints as Assert;

final class CartLineRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Sku is required.', normalizer: 'trim')]
        public string $sku = '',
        #[Assert\NotNull(message: 'Price is required.')]
        #[Assert\Type(type: 'number', message: 'Price must be a number.')]
        #[Assert\PositiveOrZero(message: 'Price must be zero or greater.')]
        public mixed $price = null,
        #[Assert\NotNull(message: 'Quantity is required.')]
        #[Assert\Type(type: 'int', message: 'Quantity must be a whole number.')]
        #[Assert\Positive(message: 'Quantity must be at least 1.')]
        public mixed $qty = null,
    ) {
    }
}
