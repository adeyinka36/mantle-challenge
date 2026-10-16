<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class CheckoutRequest
{
    public const CUSTOMER_TYPES = ['regular', 'vip', 'staff', 'trade'];

    #[Assert\NotBlank(message: 'Email is required.', normalizer: 'trim')]
    #[Assert\Email(message: 'Email is not valid.')]
    public ?string $email = null;

    #[Assert\Choice(choices: self::CUSTOMER_TYPES, message: 'Customer type is not supported.')]
    public string $customerType = 'regular';

    /**
     * @var list<CartLineRequest>
     */
    #[Assert\Count(min: 1, minMessage: 'Cart should not be empty.')]
    #[Assert\Valid]
    public array $cart = [];

    #[Assert\Type(type: 'bool', message: 'Gift must be true or false.')]
    public mixed $gift = false;

    public ?string $recipientEmail = null;

    #[Assert\Type(type: 'bool', message: 'Sample must be true or false.')]
    public mixed $sample = false;

    #[Assert\Type(type: 'string', message: 'Mobile must be a string.')]
    public mixed $mobile = null;

    #[Assert\Callback]
    public function validateGiftRecipient(ExecutionContextInterface $context): void
    {
        if ($this->gift !== true) {
            return;
        }

        $recipientEmail = is_string($this->recipientEmail) ? trim($this->recipientEmail) : '';
        if ($recipientEmail === '') {
            $context->buildViolation("A gift needs the recipient's email.")
                ->atPath('recipientEmail')
                ->addViolation();

            return;
        }

        $violations = $context->getValidator()->validate(
            $recipientEmail,
            new Assert\Email(message: 'Recipient email is not valid.'),
        );
        foreach ($violations as $violation) {
            $context->buildViolation((string) $violation->getMessage())
                ->atPath('recipientEmail')
                ->addViolation();
        }
    }
}
