<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Stand-in so the app can boot without an SMS provider.
 * Bind a real sender to SmsSender when one exists. Tests use a recording sender.
 */
#[AsAlias(SmsSender::class)]
final class NullSmsSender implements SmsSender
{
    public function send(string $mobile, string $message): void
    {
    }
}
