<?php
declare(strict_types=1);

namespace App\Service\Checkout\EventListener;

use App\Service\Checkout\Event\OrderPlaced;
use App\Service\Checkout\SmsSender;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class SendOrderSms
{
    public function __construct(
        private SmsSender $smsSender,
    ) {
    }

    public function __invoke(OrderPlaced $event): void
    {
        if ($event->mobile === null || $event->mobile === '') {
            return;
        }

        $this->smsSender->send($event->mobile, $event->confirmationText());
    }
}
