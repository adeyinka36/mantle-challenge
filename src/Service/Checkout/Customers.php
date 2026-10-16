<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use App\Entity\Customer;
use App\Enum\CustomerType;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Customers
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findOrCreate(string $email, CustomerType $customerType): Customer
    {
        $customer = $this->entityManager->getRepository(Customer::class)->findOneBy(['email' => $email]);

        if (!$customer) {
            $customer = new Customer();
            $customer->setEmail($email);
            $this->entityManager->persist($customer);
        }

        $customer->setType($customerType);

        return $customer;
    }
}
