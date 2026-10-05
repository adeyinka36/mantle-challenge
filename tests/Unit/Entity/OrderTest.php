<?php
declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Order;
use App\Entity\OrderItem;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testNewOrderIsAPlainOrderWithNoItems(): void
    {
        $order = new Order();

        $this->assertFalse($order->isGift());
        $this->assertFalse($order->isSample());
        $this->assertNull($order->getRecipientEmail());
        $this->assertCount(0, $order->getItems());
    }

    public function testAddingAnItemLinksItBackToTheOrder(): void
    {
        $order = new Order();
        $item = (new OrderItem())->setSku('MUG-01')->setPrice(12.50)->setQty(2);

        $order->addItem($item);

        $this->assertSame([$item], $order->getItems()->toArray());
        $this->assertSame($order, $item->getOrder());
    }

    public function testAddingTheSameItemTwiceKeepsOneCopy(): void
    {
        $order = new Order();
        $item = (new OrderItem())->setSku('MUG-01')->setPrice(12.50)->setQty(2);

        $order->addItem($item);
        $order->addItem($item);

        $this->assertCount(1, $order->getItems());
    }
}
