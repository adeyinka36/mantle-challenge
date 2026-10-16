<?php
declare(strict_types=1);

namespace App\Service\Checkout\Pricing;

use App\Enum\CustomerType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Customer discounts match on type. FreeSamplePrice matches any sample and is
 * tagged with a higher priority, so it runs first and the others are skipped.
 */
#[AutoconfigureTag('app.checkout.discount')]
interface Discount
{
    public function supports(CustomerType $customerType, bool $isSample): bool;

    public function apply(float $subtotal): float;
}
