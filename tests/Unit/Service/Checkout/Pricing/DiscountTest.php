<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout\Pricing;

use App\Enum\CustomerType;
use App\Service\Checkout\Pricing\FreeSamplePrice;
use App\Service\Checkout\Pricing\StaffDiscount;
use App\Service\Checkout\Pricing\StudentDiscount;
use App\Service\Checkout\Pricing\TradeDiscount;
use App\Service\Checkout\Pricing\VipDiscount;
use PHPUnit\Framework\TestCase;

final class DiscountTest extends TestCase
{
    public function testVipTakesTwentyPercentOff(): void
    {
        $discount = new VipDiscount();

        $this->assertTrue($discount->supports(CustomerType::Vip, false));
        $this->assertFalse($discount->supports(CustomerType::Staff, false));
        $this->assertEqualsWithDelta(20.0, $discount->apply(25.0), 0.001);
    }

    public function testStaffTakesFiftyPercentOff(): void
    {
        $discount = new StaffDiscount();

        $this->assertTrue($discount->supports(CustomerType::Staff, false));
        $this->assertFalse($discount->supports(CustomerType::Vip, false));
        $this->assertEqualsWithDelta(12.5, $discount->apply(25.0), 0.001);
    }

    public function testTradeTakesTenPercentOffThenSubtractsFivePounds(): void
    {
        $discount = new TradeDiscount();

        $this->assertTrue($discount->supports(CustomerType::Trade, false));
        $this->assertFalse($discount->supports(CustomerType::Vip, false));
        $this->assertEqualsWithDelta(17.5, $discount->apply(25.0), 0.001);
    }

    public function testStudentTakesFifteenPercentOff(): void
    {
        $discount = new StudentDiscount();

        $this->assertTrue($discount->supports(CustomerType::Student, false));
        $this->assertFalse($discount->supports(CustomerType::Vip, false));
        $this->assertEqualsWithDelta(21.25, $discount->apply(25.0), 0.001);
    }

    public function testFreeSampleCostsNothing(): void
    {
        $discount = new FreeSamplePrice();

        $this->assertTrue($discount->supports(CustomerType::Vip, true));
        $this->assertFalse($discount->supports(CustomerType::Vip, false));
        $this->assertEqualsWithDelta(0.0, $discount->apply(25.0), 0.001);
    }
}
