<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout;

use App\Entity\Customer;
use App\Enum\CustomerType;
use App\Service\Checkout\OrderFactory;
use App\Service\Checkout\PlaceOrderData;
use App\Service\Checkout\Pricing\FreeSamplePrice;
use App\Service\Checkout\Pricing\PriceCalculator;
use App\Service\Checkout\Pricing\TradeDiscount;
use App\Service\Checkout\Pricing\VipDiscount;
use PHPUnit\Framework\TestCase;

final class OrderFactoryTest extends TestCase
{
    private OrderFactory $orderFactory;

    protected function setUp(): void
    {
        $this->orderFactory = new OrderFactory(new PriceCalculator([
            new FreeSamplePrice(),
            new VipDiscount(),
        ]), 3.50);
    }

    public function testBuildsTheOrderFromTheCart(): void
    {
        $order = $this->orderFactory->fromCheckout($this->checkout(), $this->customer());

        $this->assertSame('jo@example.com', $order->getCustomer()?->getEmail());
        $this->assertCount(1, $order->getItems());
        $this->assertSame('MUG-01', $order->getItems()[0]->getSku());
        $this->assertSame(10.0, $order->getItems()[0]->getPrice());
        $this->assertSame(2, $order->getItems()[0]->getQty());
        $this->assertEqualsWithDelta(16.0, $order->getTotal(), 0.001);
        $this->assertNotNull($order->getCreatedAt());
        $this->assertFalse($order->isGift());
        $this->assertFalse($order->isSample());
    }

    public function testASampleIsFree(): void
    {
        $order = $this->orderFactory->fromCheckout(
            $this->checkout(sample: true),
            $this->customer(),
        );

        $this->assertTrue($order->isSample());
        $this->assertEqualsWithDelta(0.0, $order->getTotal(), 0.001);
    }

    public function testAGiftIncludesGiftWrap(): void
    {
        $order = $this->orderFactory->fromCheckout(
            $this->checkout(gift: true, recipientEmail: 'sam@example.com'),
            $this->customer(),
        );

        $this->assertTrue($order->isGift());
        $this->assertSame('sam@example.com', $order->getRecipientEmail());
        $this->assertEqualsWithDelta(19.50, $order->getTotal(), 0.001);
    }

    public function testASampleGiftStaysFree(): void
    {
        $order = $this->orderFactory->fromCheckout(
            $this->checkout(gift: true, recipientEmail: 'sam@example.com', sample: true),
            $this->customer(),
        );

        $this->assertTrue($order->isGift());
        $this->assertTrue($order->isSample());
        $this->assertEqualsWithDelta(0.0, $order->getTotal(), 0.001);
    }

    public function testASmallTradeOrderCannotGoBelowZero(): void
    {
        $orderFactory = new OrderFactory(new PriceCalculator([
            new TradeDiscount(),
        ]), 3.50);

        $order = $orderFactory->fromCheckout(
            new PlaceOrderData(
                email: 'jo@example.com',
                customerType: CustomerType::Trade,
                cart: [['sku' => 'TEA-01', 'price' => 5.0, 'qty' => 1]],
            ),
            (new Customer())->setEmail('jo@example.com')->setType(CustomerType::Trade),
        );

        $this->assertEqualsWithDelta(0.0, $order->getTotal(), 0.001);
    }

    public function testGiftWrapIsAddedBeforeTheTotalIsFloored(): void
    {
        $orderFactory = new OrderFactory(new PriceCalculator([
            new TradeDiscount(),
        ]), 3.50);

        $order = $orderFactory->fromCheckout(
            new PlaceOrderData(
                email: 'jo@example.com',
                customerType: CustomerType::Trade,
                cart: [['sku' => 'TEA-01', 'price' => 5.0, 'qty' => 1]],
                gift: true,
                recipientEmail: 'sam@example.com',
            ),
            (new Customer())->setEmail('jo@example.com')->setType(CustomerType::Trade),
        );

        $this->assertEqualsWithDelta(3.0, $order->getTotal(), 0.001);
    }

    private function customer(): Customer
    {
        return (new Customer())->setEmail('jo@example.com')->setType(CustomerType::Vip);
    }

    private function checkout(bool $gift = false, ?string $recipientEmail = null, bool $sample = false): PlaceOrderData
    {
        return new PlaceOrderData(
            email: 'jo@example.com',
            customerType: CustomerType::Vip,
            cart: [['sku' => 'MUG-01', 'price' => 10.0, 'qty' => 2]],
            gift: $gift,
            recipientEmail: $recipientEmail,
            sample: $sample,
        );
    }
}
