<?php
declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\CheckoutController;
use App\Entity\Customer;
use App\Entity\Order;
use App\Tests\Double\InMemoryMailer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Characterisation tests: they pin what checkout() does today, so it can be
 * refactored safely. Gift orders and small trade orders are deliberately not
 * covered here, as their current behaviour is the subject of CHK-102 and CHK-104.
 *
 * The controller writes its own log file, so these tests point it at a
 * throwaway temp directory.
 */
final class CheckoutControllerTest extends TestCase
{
    private InMemoryMailer $mailer;
    private EntityManagerInterface $em;
    private ?Customer $existingCustomer = null;
    /** @var list<object> */
    private array $persisted = [];
    private string $logsDir;

    protected function setUp(): void
    {
        $this->mailer = new InMemoryMailer();
        $this->logsDir = sys_get_temp_dir() . '/checkout-test-' . bin2hex(random_bytes(4));

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturnCallback(fn () => $this->existingCustomer);

        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->em->method('getRepository')->willReturn($repository);
        $this->em->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });
    }

    protected function tearDown(): void
    {
        @unlink($this->logsDir . '/orders.log');
        @rmdir($this->logsDir);
    }

    /**
     * @return iterable<string, array{string, float}>
     */
    public static function customerTypes(): iterable
    {
        // cart subtotal is 2 x 10.00 + 1 x 5.00 = 25.00
        yield 'regular pays full price' => ['regular', 25.00];
        yield 'vip gets 20% off' => ['vip', 20.00];
        yield 'staff gets 50% off' => ['staff', 12.50];
        yield 'trade gets 10% off minus 5.00' => ['trade', 17.50];
        yield 'unknown type pays full price' => ['wholesale', 25.00];
    }

    #[DataProvider('customerTypes')]
    public function testDiscountIsAppliedByCustomerType(string $type, float $expectedTotal): void
    {
        $response = $this->checkout(['customerType' => $type]);

        $this->assertSame(200, $response['status']);
        $this->assertEqualsWithDelta($expectedTotal, $response['body']['total'], 0.001);
        $this->assertEqualsWithDelta($expectedTotal, $this->persistedOrder()->getTotal(), 0.001);
    }

    public function testMissingCustomerTypeIsTreatedAsRegular(): void
    {
        $response = $this->checkout(['customerType' => null]);

        $this->assertEqualsWithDelta(25.00, $response['body']['total'], 0.001);
        $this->assertSame('regular', $this->persistedOrder()->getCustomer()?->getType());
    }

    public function testOrderIsPersistedWithItsItems(): void
    {
        $this->checkout();

        $order = $this->persistedOrder();
        $this->assertCount(2, $order->getItems());
        $this->assertSame('MUG-01', $order->getItems()[0]->getSku());
        $this->assertSame(10.00, $order->getItems()[0]->getPrice());
        $this->assertSame(2, $order->getItems()[0]->getQty());
        $this->assertNotNull($order->getCreatedAt());
    }

    public function testNewCustomerIsCreatedFromTheRequest(): void
    {
        $this->checkout(['email' => 'new@example.com', 'customerType' => 'vip']);

        $customers = array_values(array_filter($this->persisted, fn (object $e) => $e instanceof Customer));
        $this->assertCount(1, $customers);
        $this->assertSame('new@example.com', $customers[0]->getEmail());
        $this->assertSame('vip', $customers[0]->getType());
        $this->assertSame($customers[0], $this->persistedOrder()->getCustomer());
    }

    public function testExistingCustomerIsReusedAndTheirTypeUpdated(): void
    {
        $this->existingCustomer = (new Customer())->setEmail('jo@example.com')->setType('regular');

        $this->checkout(['customerType' => 'staff']);

        $this->assertSame($this->existingCustomer, $this->persistedOrder()->getCustomer());
        $this->assertSame('staff', $this->existingCustomer->getType());
        $this->assertEmpty(array_filter($this->persisted, fn (object $e) => $e instanceof Customer));
    }

    public function testPurchaserReceivesAConfirmationWithTheTotal(): void
    {
        $this->checkout(['customerType' => 'vip']);

        $this->assertCount(1, $this->mailer->sent);
        $email = $this->mailer->sent[0];
        $this->assertSame('jo@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('Your order', $email->getSubject());
        $this->assertSame('Thanks for your order! Total: £20.00', $email->getTextBody());
    }

    public function testFreeSampleCostsNothingAndSendsASampleEmail(): void
    {
        $response = $this->checkout([
            'sample' => true,
            'customerType' => 'vip',
            'cart' => [['sku' => 'TEA-SAMPLER', 'price' => 4.00, 'qty' => 1]],
        ]);

        $this->assertEqualsWithDelta(0.0, $response['body']['total'], 0.001);
        $this->assertTrue($this->persistedOrder()->isSample());
        $this->assertEqualsWithDelta(0.0, $this->persistedOrder()->getTotal(), 0.001);
        $this->assertSame('Your free sample of TEA-SAMPLER is on its way!', $this->mailer->sent[0]->getTextBody());
    }

    public function testEmptyCartIsRejectedWithoutSideEffects(): void
    {
        $response = $this->checkout(['cart' => []]);

        $this->assertSame(500, $response['status']);
        $this->assertSame(['error' => 'Empty cart'], $response['body']);
        $this->assertSame([], $this->persisted);
        $this->assertSame([], $this->mailer->sent);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    private function checkout(array $overrides = []): array
    {
        $payload = array_merge([
            'email' => 'jo@example.com',
            'customerType' => 'regular',
            'cart' => [
                ['sku' => 'MUG-01', 'price' => 10.00, 'qty' => 2],
                ['sku' => 'TEA-01', 'price' => 5.00, 'qty' => 1],
            ],
        ], $overrides);

        $container = new Container();
        $container->set('parameter_bag', new ParameterBag(['kernel.logs_dir' => $this->logsDir]));

        $controller = new CheckoutController();
        $controller->setContainer($container);

        $request = Request::create('/checkout', 'POST', content: json_encode($payload, \JSON_THROW_ON_ERROR));
        $response = $controller->checkout($request, $this->em, $this->mailer);

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR),
        ];
    }

    private function persistedOrder(): Order
    {
        $orders = array_values(array_filter($this->persisted, fn (object $e) => $e instanceof Order));
        $this->assertCount(1, $orders, 'Expected exactly one order to be persisted');

        return $orders[0];
    }
}
