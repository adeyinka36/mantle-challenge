<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\Checkout\Event\OrderPlaced;
use App\Service\Checkout\Pricing\PriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class PlaceOrder
{
    public const GIFT_WRAP_PRICE = 3.50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PriceCalculator $priceCalculator,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(PlaceOrderData $checkout): PlaceOrderResult
    {
        $customer = $this->entityManager->getRepository(Customer::class)->findOneBy(['email' => $checkout->email]);
        if (!$customer) {
            $customer = new Customer();
            $customer->setEmail($checkout->email);
            $this->entityManager->persist($customer);
        }
        $customer->setType($checkout->customerType);

        $order = new Order();
        $order->setCustomer($customer);
        $order->setCreatedAt(new \DateTimeImmutable());

        $subtotal = 0.0;
        foreach ($checkout->cart as $cartLine) {
            $item = new OrderItem();
            $item->setSku($cartLine->sku);
            $item->setPrice($cartLine->price);
            $item->setQty($cartLine->qty);
            $order->addItem($item);

            $subtotal += $cartLine->price * $cartLine->qty;
        }

        $order->setSample($checkout->sample);
        $total = $this->priceCalculator->total($subtotal, $checkout->customerType, $checkout->sample);

        if ($checkout->gift) {
            // gifting team: send it to the recipient instead
            $order->setGift(true);
            $order->setRecipientEmail($checkout->recipientEmail);
        }

        $order->setTotal((float) $total);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        if ($order->isGift()) {
            $total += self::GIFT_WRAP_PRICE;
        }

        $this->dispatcher->dispatch(new OrderPlaced(
            order: $order,
            chargedTotal: (float) $total,
            purchaserEmail: $checkout->email,
            mobile: $checkout->mobile,
        ));

        return new PlaceOrderResult($order->getId(), (float) $total);
    }
}
