<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\Checkout\Pricing\PriceCalculator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OrderFactory
{
    public function __construct(
        private PriceCalculator $priceCalculator,
        #[Autowire('%env(float:GIFT_WRAP_PRICE)%')]
        private float $giftWrapPrice,
    ) {
    }

    public function fromCheckout(PlaceOrderData $checkout, Customer $customer): Order
    {
        $order = new Order();
        $order->setCustomer($customer);
        $order->setCreatedAt(new \DateTimeImmutable());

        $subtotal = 0.0;
        foreach ($checkout->cart as $cartLine) {
            $item = new OrderItem();
            $item->setSku($cartLine['sku']);
            $item->setPrice((float) $cartLine['price']);
            $item->setQty((int) $cartLine['qty']);
            $order->addItem($item);

            $subtotal += (float) $cartLine['price'] * (int) $cartLine['qty'];
        }

        $order->setSample($checkout->sample);

        $total = $this->priceCalculator->total($subtotal, $checkout->customerType, $checkout->sample);
        if ($checkout->gift && !$checkout->sample) {
            $total += $this->giftWrapPrice;
        }
        $order->setTotal($total);

        if ($checkout->gift) {
            $order->setGift(true);
            $order->setRecipientEmail($checkout->recipientEmail);
        }

        return $order;
    }
}
