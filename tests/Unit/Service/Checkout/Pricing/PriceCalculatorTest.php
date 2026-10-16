<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout\Pricing;

use App\Enum\CustomerType;
use App\Service\Checkout\Pricing\FreeSamplePrice;
use App\Service\Checkout\Pricing\PriceCalculator;
use App\Service\Checkout\Pricing\StaffDiscount;
use App\Service\Checkout\Pricing\StudentDiscount;
use App\Service\Checkout\Pricing\TradeDiscount;
use App\Service\Checkout\Pricing\VipDiscount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PriceCalculator([
            new FreeSamplePrice(),
            new VipDiscount(),
            new StaffDiscount(),
            new TradeDiscount(),
            new StudentDiscount(),
        ]);
    }

    /**
     * @return iterable<string, array{CustomerType, bool, float}>
     */
    public static function totals(): iterable
    {
        yield 'regular pays full price' => [CustomerType::Regular, false, 25.0];
        yield 'vip gets 20% off' => [CustomerType::Vip, false, 20.0];
        yield 'staff gets 50% off' => [CustomerType::Staff, false, 12.5];
        yield 'trade gets 10% off minus 5.00' => [CustomerType::Trade, false, 17.5];
        yield 'student gets 15% off' => [CustomerType::Student, false, 21.25];
        yield 'sample is free even for a vip' => [CustomerType::Vip, true, 0.0];
    }

    #[DataProvider('totals')]
    public function testTotalFollowsTheFirstMatchingRule(CustomerType $customerType, bool $isSample, float $expectedTotal): void
    {
        $total = $this->calculator->total(25.0, $customerType, $isSample);

        $this->assertEqualsWithDelta($expectedTotal, $total, 0.001);
    }
}
