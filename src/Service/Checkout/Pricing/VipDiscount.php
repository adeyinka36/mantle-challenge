<?php
declare(strict_types=1);

namespace App\Service\Checkout\Pricing;

final class VipDiscount implements Discount
{
    public function supports(string $customerType, bool $isSample): bool
    {
        return $customerType === 'vip';
    }

    public function apply(float $subtotal): float
    {
        return $subtotal * 0.8;
    }
}
