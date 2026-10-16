<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout;

use App\Service\Checkout\NullSmsSender;
use PHPUnit\Framework\TestCase;

final class NullSmsSenderTest extends TestCase
{
    private string $logDirectory;

    protected function setUp(): void
    {
        $this->logDirectory = sys_get_temp_dir() . '/sms-log-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        @unlink($this->logDirectory . '/sms.log');
        @rmdir($this->logDirectory);
    }

    public function testWritesALineForTheMobileAndMessage(): void
    {
        $sender = new NullSmsSender($this->logDirectory);

        $sender->send('07535996987', 'Thanks for your order! Total: £20.00');

        $line = (string) file_get_contents($this->logDirectory . '/sms.log');
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2} text sent to 07535996987, Thanks for your order! Total: £20\.00\n$/',
            $line,
        );
    }
}
