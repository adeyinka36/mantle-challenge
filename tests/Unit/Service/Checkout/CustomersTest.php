<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout;

use App\Entity\Customer;
use App\Enum\CustomerType;
use App\Service\Checkout\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class CustomersTest extends TestCase
{
    private ?Customer $existingCustomer = null;

    /** @var list<object> */
    private array $persisted = [];

    private Customers $customers;

    protected function setUp(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturnCallback(fn () => $this->existingCustomer);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });

        $this->customers = new Customers($entityManager);
    }

    public function testCreatesACustomerWhenTheEmailIsNew(): void
    {
        $customer = $this->customers->findOrCreate('new@example.com', CustomerType::Vip);

        $this->assertSame([$customer], $this->persisted);
        $this->assertSame('new@example.com', $customer->getEmail());
        $this->assertSame(CustomerType::Vip, $customer->getType());
    }

    public function testReusesAnExistingCustomerAndUpdatesTheirType(): void
    {
        $this->existingCustomer = (new Customer())->setEmail('jo@example.com')->setType(CustomerType::Regular);

        $customer = $this->customers->findOrCreate('jo@example.com', CustomerType::Staff);

        $this->assertSame($this->existingCustomer, $customer);
        $this->assertSame(CustomerType::Staff, $customer->getType());
        $this->assertSame([], $this->persisted);
    }
}
