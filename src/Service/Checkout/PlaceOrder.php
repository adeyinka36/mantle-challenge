<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class PlaceOrder
{
    public const GIFT_WRAP_PRICE = 3.50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        #[Autowire('%kernel.logs_dir%')]
        private readonly string $logDirectory,
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
        $customer->setType($checkout->customerType ?? 'regular');

        $order = new Order();
        $order->setCustomer($customer);
        $order->setCreatedAt(new \DateTimeImmutable());

        $total = 0;
        foreach ($checkout->cart as $cartLine) {
            $item = new OrderItem();
            $item->setSku($cartLine['sku']);
            $item->setPrice((float) $cartLine['price']);
            $item->setQty((int) $cartLine['qty']);
            $order->addItem($item);

            $total += $cartLine['price'] * $cartLine['qty'];
        }

        if ($checkout->sample) {
            // warehouse team: free product samples go through checkout too
            $order->setSample(true);
            $total = 0;
        } elseif ($customer->getType() === 'vip') {
            $total *= 0.8;
        } elseif ($customer->getType() === 'staff') {
            $total *= 0.5;
        } elseif ($customer->getType() === 'trade') {
            $total = $total * 0.9 - 5;
        }

        $recipient = $checkout->email;
        if ($checkout->gift) {
            // gifting team: send it to the recipient instead
            $order->setGift(true);
            $order->setRecipientEmail($checkout->recipientEmail);
            $recipient = (string) $checkout->recipientEmail;
        }

        $order->setTotal((float) $total);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        if ($order->isGift()) {
            $total += self::GIFT_WRAP_PRICE;
        }

        if ($order->isSample()) {
            $emailBody = 'Your free sample of ' . $checkout->cart[0]['sku'] . ' is on its way!';
        } else {
            $emailBody = 'Thanks for your order! Total: £' . number_format($total, 2);
        }

        $this->mailer->send(
            (new Email())
                ->from('shop@example.com')
                ->to($recipient)
                ->subject('Your order')
                ->text($emailBody)
        );

        $this->writeOrderLog($checkout->email);

        return new PlaceOrderResult($order->getId(), (float) $total);
    }

    private function writeOrderLog(string $email): void
    {
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0777, true);
        }

        file_put_contents(
            $this->logDirectory . '/orders.log',
            date('c') . ' order placed for ' . $email . "\n",
            FILE_APPEND
        );
    }
}
