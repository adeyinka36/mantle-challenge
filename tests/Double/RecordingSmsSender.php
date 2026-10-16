<?php
declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\Checkout\SmsSender;

final class RecordingSmsSender implements SmsSender
{
    /** @var list<array{mobile: string, message: string}> */
    public array $sent = [];

    public function send(string $mobile, string $message): void
    {
        $this->sent[] = ['mobile' => $mobile, 'message' => $message];
    }
}
