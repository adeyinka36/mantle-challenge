<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout;

use App\Enum\CustomerType;
use App\Service\Checkout\PlaceOrderData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PlaceOrderDataTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testACompleteOrderIsValid(): void
    {
        $violations = $this->validator->validate(new PlaceOrderData(
            email: 'jo@example.com',
            cart: [
                ['sku' => 'MUG-01', 'price' => 10.0, 'qty' => 2],
                ['sku' => 'TEA-01', 'price' => 5, 'qty' => 1],
            ],
        ));

        $this->assertCount(0, $violations);
    }

    public function testEmptyCartIsRejected(): void
    {
        $this->assertSame(
            ['cart' => 'Cart should not be empty.'],
            $this->errors(new PlaceOrderData(email: 'jo@example.com', cart: [])),
        );
    }

    public function testEmailMustBeValid(): void
    {
        $this->assertSame(
            ['email' => 'Email is not valid.'],
            $this->errors(new PlaceOrderData(email: 'not-an-email', cart: $this->cart())),
        );
    }

    public function testAnUnknownCustomerTypeCannotBeParsed(): void
    {
        $this->assertNull(CustomerType::tryFrom('wholesale'));
    }

    public function testAGiftNeedsTheRecipientsEmail(): void
    {
        $missing = $this->errors(new PlaceOrderData(
            email: 'jo@example.com',
            cart: $this->cart(),
            gift: true,
        ));
        $invalid = $this->errors(new PlaceOrderData(
            email: 'jo@example.com',
            cart: $this->cart(),
            gift: true,
            recipientEmail: 'not-an-email',
        ));
        $valid = $this->validator->validate(new PlaceOrderData(
            email: 'jo@example.com',
            cart: $this->cart(),
            gift: true,
            recipientEmail: 'sam@example.com',
        ));

        $this->assertSame(['recipientEmail' => "A gift needs the recipient's email."], $missing);
        $this->assertSame(['recipientEmail' => 'Recipient email is not valid.'], $invalid);
        $this->assertCount(0, $valid);
    }

    public function testCartLinePriceAndQuantityAreChecked(): void
    {
        $this->assertSame(
            [
                'cart[0][sku]' => 'Sku is required.',
                'cart[0][price]' => 'Price must be zero or greater.',
                'cart[0][qty]' => 'Quantity must be at least 1.',
            ],
            $this->errors(new PlaceOrderData(
                email: 'jo@example.com',
                cart: [['sku' => '', 'price' => -1, 'qty' => 0]],
            )),
        );
    }

    /**
     * @return array<string, string>
     */
    private function errors(PlaceOrderData $checkout): array
    {
        return $this->messages($this->validator->validate($checkout));
    }

    /**
     * @return array<string, string>
     */
    private function messages(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] ??= (string) $violation->getMessage();
        }

        return $errors;
    }

    /**
     * @return list<array{sku: string, price: float, qty: int}>
     */
    private function cart(): array
    {
        return [
            ['sku' => 'MUG-01', 'price' => 10.0, 'qty' => 2],
        ];
    }
}
