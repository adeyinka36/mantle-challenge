<?php
declare(strict_types=1);

namespace App\Service\Checkout\Pricing;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class PriceCalculator
{
    /**
     * @param iterable<Discount> $discounts Ordered by tag priority, highest first.
     */
    public function __construct(
        #[AutowireIterator('app.checkout.discount')]
        private iterable $discounts,
    ) {
    }

    public function total(float $subtotal, string $customerType, bool $isSample): float
    {
        foreach ($this->discounts as $discount) {
            if ($discount->supports($customerType, $isSample)) {
                return $discount->apply($subtotal);
            }
        }

        return $subtotal;
    }
}
