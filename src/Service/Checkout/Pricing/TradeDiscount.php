<?php
declare(strict_types=1);

namespace App\Service\Checkout\Pricing;

use App\Enum\CustomerType;

final class TradeDiscount implements Discount
{
    public function supports(CustomerType $customerType, bool $isSample): bool
    {
        return $customerType === CustomerType::Trade;
    }

    public function apply(float $subtotal): float
    {
        return $subtotal * 0.9 - 5;
    }
}
