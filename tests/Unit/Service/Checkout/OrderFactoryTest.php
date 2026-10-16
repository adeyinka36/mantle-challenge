<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout;

use App\Entity\Customer;
use App\Service\Checkout\OrderFactory;
use App\Service\Checkout\PlaceOrderData;
use App\Service\Checkout\Pricing\FreeSamplePrice;
use App\Service\Checkout\Pricing\PriceCalculator;
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
        ]));
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

    public function testAGiftIsMarkedWithoutAddingGiftWrap(): void
    {
        $order = $this->orderFactory->fromCheckout(
            $this->checkout(gift: true, recipientEmail: 'sam@example.com'),
            $this->customer(),
        );

        $this->assertTrue($order->isGift());
        $this->assertSame('sam@example.com', $order->getRecipientEmail());
        $this->assertEqualsWithDelta(16.0, $order->getTotal(), 0.001);
    }

    private function customer(): Customer
    {
        return (new Customer())->setEmail('jo@example.com')->setType('vip');
    }

    private function checkout(bool $gift = false, ?string $recipientEmail = null, bool $sample = false): PlaceOrderData
    {
        return new PlaceOrderData(
            email: 'jo@example.com',
            customerType: 'vip',
            cart: [['sku' => 'MUG-01', 'price' => 10.0, 'qty' => 2]],
            gift: $gift,
            recipientEmail: $recipientEmail,
            sample: $sample,
        );
    }
}
