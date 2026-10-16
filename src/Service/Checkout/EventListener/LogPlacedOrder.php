<?php
declare(strict_types=1);

namespace App\Service\Checkout\EventListener;

use App\Service\Checkout\Event\OrderPlaced;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class LogPlacedOrder
{
    public function __construct(
        #[Autowire('%kernel.logs_dir%')]
        private readonly string $logDirectory,
    ) {
    }

    public function __invoke(OrderPlaced $event): void
    {
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0777, true);
        }

        file_put_contents(
            $this->logDirectory . '/orders.log',
            date('c') . ' order placed for ' . $event->purchaserEmail . "\n",
            FILE_APPEND
        );
    }
}
