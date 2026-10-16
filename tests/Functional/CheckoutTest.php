<?php
declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

final class CheckoutTest extends WebTestCase
{
    public function testAGiftOrderIsSavedAndBothEmailsAreSent(): void
    {
        $client = static::createClient();
        $this->createSchema();
        $emails = new \ArrayObject();
        $this->recordEmails($emails);

        $client->jsonRequest('POST', '/checkout', [
            'email' => 'jo@example.com',
            'customerType' => 'vip',
            'cart' => [
                ['sku' => 'MUG-01', 'price' => 10.0, 'qty' => 2],
                ['sku' => 'TEA-01', 'price' => 5.0, 'qty' => 1],
            ],
            'gift' => true,
            'recipientEmail' => 'sam@example.com',
        ]);

        self::assertResponseIsSuccessful();
        $body = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertEqualsWithDelta(23.50, $body['total'], 0.001);

        $order = static::getContainer()->get(EntityManagerInterface::class)->find(Order::class, $body['id']);
        self::assertInstanceOf(Order::class, $order);
        self::assertEqualsWithDelta(23.50, $order->getTotal(), 0.001);
        self::assertTrue($order->isGift());

        $textByRecipient = [];
        foreach ($emails as $email) {
            $textByRecipient[$email->getTo()[0]->getAddress()] = $email->getTextBody();
        }
        self::assertSame('Thanks for your order! Total: £23.50', $textByRecipient['jo@example.com']);
        self::assertSame('A gift is on its way to you.', $textByRecipient['sam@example.com']);
    }

    public function testAnEmptyCartIsRejected(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/checkout', [
            'email' => 'jo@example.com',
            'customerType' => 'vip',
            'cart' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Cart should not be empty.', (string) $client->getResponse()->getContent());
    }

    private function createSchema(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /**
     * @param \ArrayObject<int, Email> $emails
     */
    private function recordEmails(\ArrayObject $emails): void
    {
        static::getContainer()->get('event_dispatcher')->addListener(
            MessageEvent::class,
            static function (MessageEvent $event) use ($emails): void {
                $message = $event->getMessage();
                if ($message instanceof Email) {
                    $emails[] = $message;
                }
            },
        );
    }
}
