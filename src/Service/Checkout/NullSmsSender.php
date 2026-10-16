<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Stand-in so the app can boot without an SMS provider.
 * Bind a real sender to SmsSender when one exists. Tests use a recording sender.
 */
#[AsAlias(SmsSender::class)]
final readonly class NullSmsSender implements SmsSender
{
    public function __construct(
        #[Autowire('%kernel.logs_dir%')]
        private string $logDirectory,
    ) {
    }

    public function send(string $mobile, string $message): void
    {
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0777, true);
        }

        file_put_contents(
            $this->logDirectory . '/sms.log',
            date('c') . ' text sent to ' . $mobile . ', ' . $message . "\n",
            FILE_APPEND
        );
    }
}
