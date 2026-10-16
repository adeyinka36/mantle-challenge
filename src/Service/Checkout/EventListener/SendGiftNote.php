<?php
declare(strict_types=1);

namespace App\Service\Checkout\EventListener;

use App\Service\Checkout\Event\OrderPlaced;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsEventListener]
final readonly class SendGiftNote
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(OrderPlaced $event): void
    {
        $recipientEmail = $event->order->getRecipientEmail();
        if (!$event->order->isGift() || $recipientEmail === null) {
            return;
        }

        $this->mailer->send(
            (new Email())
                ->from('shop@example.com')
                ->to($recipientEmail)
                ->subject('A gift for you')
                ->text('A gift is on its way to you.')
        );
    }
}
