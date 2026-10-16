<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use App\Service\Checkout\Event\OrderPlaced;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class PlaceOrder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Customers $customers,
        private OrderFactory $orderFactory,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(PlaceOrderData $checkout): PlaceOrderResult
    {
        $customer = $this->customers->findOrCreate($checkout->email, $checkout->customerType);
        $order = $this->orderFactory->fromCheckout($checkout, $customer);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(new OrderPlaced(
            order: $order,
            purchaserEmail: $checkout->email,
            mobile: $checkout->mobile,
        ));

        return new PlaceOrderResult($order->getId(), (float) $order->getTotal());
    }
}
