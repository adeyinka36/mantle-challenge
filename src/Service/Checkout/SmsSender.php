<?php
declare(strict_types=1);

namespace App\Service\Checkout;

interface SmsSender
{
    public function send(string $mobile, string $message): void;
}
