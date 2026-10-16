<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout\EventListener;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\Checkout\Event\OrderPlaced;
use App\Service\Checkout\EventListener\SendOrderSms;
use App\Tests\Double\RecordingSmsSender;
use PHPUnit\Framework\TestCase;

final class SendOrderSmsTest extends TestCase
{
    private RecordingSmsSender $smsSender;
    private SendOrderSms $listener;

    protected function setUp(): void
    {
        $this->smsSender = new RecordingSmsSender();
        $this->listener = new SendOrderSms($this->smsSender);
    }

    public function testSendsTheConfirmationWhenAMobileIsPresent(): void
    {
        ($this->listener)(new OrderPlaced($this->order(), 20.0, 'jo@example.com', '07123456789'));

        $this->assertSame([
            ['mobile' => '07123456789', 'message' => 'Thanks for your order! Total: £20.00'],
        ], $this->smsSender->sent);
    }

    public function testSendsNothingWhenMobileIsMissing(): void
    {
        ($this->listener)(new OrderPlaced($this->order(), 20.0, 'jo@example.com'));

        $this->assertSame([], $this->smsSender->sent);
    }

    public function testSampleTextHasNoPrice(): void
    {
        $order = $this->order();
        $order->setSample(true);

        ($this->listener)(new OrderPlaced($order, 0.0, 'jo@example.com', '07123456789'));

        $this->assertSame('Your free sample of TEA-01 is on its way!', $this->smsSender->sent[0]['message']);
    }

    private function order(): Order
    {
        $order = new Order();
        $item = new OrderItem();
        $item->setSku('TEA-01');
        $item->setPrice(5.0);
        $item->setQty(1);
        $order->addItem($item);

        return $order;
    }
}
