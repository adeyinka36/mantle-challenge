<?php
declare(strict_types=1);

namespace App\Service\Checkout\Pricing;

use App\Enum\CustomerType;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 100)]
final class FreeSamplePrice implements Discount
{
    public function supports(CustomerType $customerType, bool $isSample): bool
    {
        return $isSample;
    }

    public function apply(float $subtotal): float
    {
        return 0.0;
    }
}
