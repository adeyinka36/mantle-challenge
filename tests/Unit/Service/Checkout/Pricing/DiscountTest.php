<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout\Pricing;

use App\Service\Checkout\Pricing\FreeSamplePrice;
use App\Service\Checkout\Pricing\StaffDiscount;
use App\Service\Checkout\Pricing\TradeDiscount;
use App\Service\Checkout\Pricing\VipDiscount;
use PHPUnit\Framework\TestCase;

final class DiscountTest extends TestCase
{
    public function testVipTakesTwentyPercentOff(): void
    {
        $discount = new VipDiscount();

        $this->assertTrue($discount->supports('vip', false));
        $this->assertFalse($discount->supports('staff', false));
        $this->assertEqualsWithDelta(20.0, $discount->apply(25.0), 0.001);
    }

    public function testStaffTakesFiftyPercentOff(): void
    {
        $discount = new StaffDiscount();

        $this->assertTrue($discount->supports('staff', false));
        $this->assertFalse($discount->supports('vip', false));
        $this->assertEqualsWithDelta(12.5, $discount->apply(25.0), 0.001);
    }

    public function testTradeTakesTenPercentOffThenSubtractsFivePounds(): void
    {
        $discount = new TradeDiscount();

        $this->assertTrue($discount->supports('trade', false));
        $this->assertFalse($discount->supports('vip', false));
        $this->assertEqualsWithDelta(17.5, $discount->apply(25.0), 0.001);
    }

    public function testFreeSampleCostsNothing(): void
    {
        $discount = new FreeSamplePrice();

        $this->assertTrue($discount->supports('vip', true));
        $this->assertFalse($discount->supports('vip', false));
        $this->assertEqualsWithDelta(0.0, $discount->apply(25.0), 0.001);
    }
}
