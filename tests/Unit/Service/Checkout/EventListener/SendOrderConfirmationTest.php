<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout\EventListener;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\Checkout\Event\OrderPlaced;
use App\Service\Checkout\EventListener\SendOrderConfirmation;
use App\Tests\Double\InMemoryMailer;
use PHPUnit\Framework\TestCase;

final class SendOrderConfirmationTest extends TestCase
{
    private InMemoryMailer $mailer;
    private SendOrderConfirmation $listener;

    protected function setUp(): void
    {
        $this->mailer = new InMemoryMailer();
        $this->listener = new SendOrderConfirmation($this->mailer);
    }

    public function testPurchaserReceivesThePricedTotal(): void
    {
        ($this->listener)(new OrderPlaced($this->order(), 20.0, 'jo@example.com'));

        $this->assertCount(1, $this->mailer->sent);
        $email = $this->mailer->sent[0];
        $this->assertSame('shop@example.com', $email->getFrom()[0]->getAddress());
        $this->assertSame('jo@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('Your order', $email->getSubject());
        $this->assertSame('Thanks for your order! Total: £20.00', $email->getTextBody());
    }

    public function testSampleEmailNamesTheSkuAndHasNoPrice(): void
    {
        $order = $this->order();
        $order->setSample(true);

        ($this->listener)(new OrderPlaced($order, 0.0, 'jo@example.com'));

        $this->assertSame(
            'Your free sample of MUG-01 is on its way!',
            $this->mailer->sent[0]->getTextBody(),
        );
    }

    public function testGiftEmailGoesToTheRecipientWithThePrice(): void
    {
        // Current behaviour. CHK-102 sends the purchaser the price and the recipient a note with no price.
        $order = $this->order();
        $order->setGift(true);
        $order->setRecipientEmail('sam@example.com');

        ($this->listener)(new OrderPlaced($order, 23.5, 'jo@example.com'));

        $this->assertSame('sam@example.com', $this->mailer->sent[0]->getTo()[0]->getAddress());
        $this->assertSame('Thanks for your order! Total: £23.50', $this->mailer->sent[0]->getTextBody());
    }

    private function order(): Order
    {
        $order = new Order();
        $order->setCustomer((new Customer())->setEmail('jo@example.com')->setType('regular'));

        $item = new OrderItem();
        $item->setSku('MUG-01');
        $item->setPrice(10.0);
        $item->setQty(2);
        $order->addItem($item);

        return $order;
    }
}
