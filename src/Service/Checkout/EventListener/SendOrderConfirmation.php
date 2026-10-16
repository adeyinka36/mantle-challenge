<?php
declare(strict_types=1);

namespace App\Service\Checkout\EventListener;

use App\Service\Checkout\Event\OrderPlaced;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsEventListener]
final class SendOrderConfirmation
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(OrderPlaced $event): void
    {
        $this->mailer->send(
            (new Email())
                ->from('shop@example.com')
                ->to($event->emailRecipient())
                ->subject('Your order')
                ->text($event->confirmationText())
        );
    }
}
