<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    public const GIFT_WRAP_PRICE = 3.50;

    #[Route('/checkout', methods: ['POST'])]
    public function checkout(Request $request, EntityManagerInterface $em, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['cart'])) {
            return new JsonResponse(['error' => 'Empty cart'], 500);
        }

        // find or create the customer
        $customer = $em->getRepository(Customer::class)->findOneBy(['email' => $data['email']]);
        if (!$customer) {
            $customer = new Customer();
            $customer->setEmail($data['email']);
            $em->persist($customer);
        }
        $customer->setType($data['customerType'] ?? 'regular');

        $order = new Order();
        $order->setCustomer($customer);
        $order->setCreatedAt(new \DateTimeImmutable());

        $total = 0;
        foreach ($data['cart'] as $row) {
            $item = new OrderItem();
            $item->setSku($row['sku']);
            $item->setPrice((float) $row['price']);
            $item->setQty((int) $row['qty']);
            $order->addItem($item);

            $total += $row['price'] * $row['qty'];
        }

        if (!empty($data['sample'])) {
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

        $to = $data['email'];
        if (!empty($data['gift'])) {
            // gifting team: send it to the recipient instead
            $order->setGift(true);
            $order->setRecipientEmail($data['recipientEmail']);
            $to = $data['recipientEmail'];
        }

        $order->setTotal((float) $total);
        $em->persist($order);
        $em->flush();

        if ($order->isGift()) {
            $total += self::GIFT_WRAP_PRICE;
        }

        if ($order->isSample()) {
            $body = 'Your free sample of ' . $data['cart'][0]['sku'] . ' is on its way!';
        } else {
            $body = 'Thanks for your order! Total: £' . number_format($total, 2);
        }
        $mailer->send(
            (new Email())
                ->from('shop@example.com')
                ->to($to)
                ->subject('Your order')
                ->text($body)
        );

        $logDir = $this->getParameter('kernel.logs_dir');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        file_put_contents($logDir . '/orders.log', date('c') . ' order placed for ' . $data['email'] . "\n", FILE_APPEND);

        return new JsonResponse(['id' => $order->getId(), 'total' => $total]);
    }
}
