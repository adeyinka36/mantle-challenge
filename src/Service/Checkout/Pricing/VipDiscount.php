<?php
declare(strict_types=1);

namespace App\Service\Checkout\Pricing;

use App\Enum\CustomerType;

final class VipDiscount implements Discount
{
    public function supports(CustomerType $customerType, bool $isSample): bool
    {
        return $customerType === CustomerType::Vip;
    }

    public function apply(float $subtotal): float
    {
        return $subtotal * 0.8;
    }
}
