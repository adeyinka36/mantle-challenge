<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout\EventListener;

use App\Entity\Order;
use App\Service\Checkout\Event\OrderPlaced;
use App\Service\Checkout\EventListener\SendGiftNote;
use App\Tests\Double\InMemoryMailer;
use PHPUnit\Framework\TestCase;

final class SendGiftNoteTest extends TestCase
{
    private InMemoryMailer $mailer;
    private SendGiftNote $listener;

    protected function setUp(): void
    {
        $this->mailer = new InMemoryMailer();
        $this->listener = new SendGiftNote($this->mailer);
    }

    public function testRecipientReceivesANoteWithoutAPrice(): void
    {
        $order = new Order();
        $order->setGift(true);
        $order->setRecipientEmail('sam@example.com');
        $order->setTotal(23.5);

        ($this->listener)(new OrderPlaced($order, 'jo@example.com'));

        $this->assertCount(1, $this->mailer->sent);
        $email = $this->mailer->sent[0];
        $this->assertSame('sam@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('A gift for you', $email->getSubject());
        $this->assertSame('A gift is on its way to you.', $email->getTextBody());
        $this->assertStringNotContainsString('£', (string) $email->getTextBody());
    }

    public function testANormalOrderSendsNothing(): void
    {
        ($this->listener)(new OrderPlaced(new Order(), 'jo@example.com'));

        $this->assertSame([], $this->mailer->sent);
    }
}
