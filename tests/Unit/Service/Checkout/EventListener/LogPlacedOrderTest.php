<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout\EventListener;

use App\Entity\Order;
use App\Service\Checkout\Event\OrderPlaced;
use App\Service\Checkout\EventListener\LogPlacedOrder;
use PHPUnit\Framework\TestCase;

final class LogPlacedOrderTest extends TestCase
{
    private string $logDirectory;

    protected function setUp(): void
    {
        $this->logDirectory = sys_get_temp_dir() . '/order-log-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        @unlink($this->logDirectory . '/orders.log');
        @rmdir($this->logDirectory);
    }

    public function testWritesOneLineForThePurchaser(): void
    {
        $listener = new LogPlacedOrder($this->logDirectory);

        $listener(new OrderPlaced(new Order(), 'jo@example.com'));

        $line = (string) file_get_contents($this->logDirectory . '/orders.log');
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2} order placed for jo@example.com\n$/',
            $line,
        );
    }
}
