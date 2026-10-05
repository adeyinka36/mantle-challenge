<?php
declare(strict_types=1);

namespace App\Tests\Double;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class InMemoryMailer implements MailerInterface
{
    /** @var list<Email> */
    public array $sent = [];

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        \assert($message instanceof Email);
        $this->sent[] = $message;
    }
}
