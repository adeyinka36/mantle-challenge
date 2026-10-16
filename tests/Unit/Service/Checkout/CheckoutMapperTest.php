<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Checkout;

use App\Service\Checkout\CheckoutMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CheckoutMapperTest extends TestCase
{
    private CheckoutMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new CheckoutMapper(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testAValidPayloadMapsToCheckout(): void
    {
        $mapped = $this->mapper->map($this->payload(['customerType' => null]));

        $this->assertSame([], $mapped->errors);
        $checkout = $mapped->checkout();
        $this->assertSame('jo@example.com', $checkout->email);
        $this->assertSame('regular', $checkout->customerType);
        $this->assertCount(2, $checkout->cart);
        $this->assertSame('MUG-01', $checkout->cart[0]->sku);
        $this->assertSame(10.0, $checkout->cart[0]->price);
        $this->assertSame(2, $checkout->cart[0]->qty);
        $this->assertNull($checkout->mobile);
    }

    public function testEmptyCartIsRejected(): void
    {
        $mapped = $this->mapper->map($this->payload(['cart' => []]));

        $this->assertSame(['cart' => 'Cart should not be empty.'], $mapped->errors);
    }

    public function testEmailIsRequiredAndMustBeValid(): void
    {
        $missing = $this->mapper->map($this->payload(['email' => null]));
        $invalid = $this->mapper->map($this->payload(['email' => 'not-an-email']));

        $this->assertSame(['email' => 'Email is required.'], $missing->errors);
        $this->assertSame(['email' => 'Email is not valid.'], $invalid->errors);
    }

    public function testUnknownCustomerTypeIsRejected(): void
    {
        $mapped = $this->mapper->map($this->payload(['customerType' => 'wholesale']));

        $this->assertSame(['customerType' => 'Customer type is not supported.'], $mapped->errors);
    }

    public function testAGiftNeedsTheRecipientsEmail(): void
    {
        $missing = $this->mapper->map($this->payload(['gift' => true]));
        $invalid = $this->mapper->map($this->payload([
            'gift' => true,
            'recipientEmail' => 'not-an-email',
        ]));
        $valid = $this->mapper->map($this->payload([
            'gift' => true,
            'recipientEmail' => 'sam@example.com',
        ]));

        $this->assertSame(['recipientEmail' => "A gift needs the recipient's email."], $missing->errors);
        $this->assertSame(['recipientEmail' => 'Recipient email is not valid.'], $invalid->errors);
        $this->assertSame([], $valid->errors);
        $this->assertTrue($valid->checkout()->gift);
        $this->assertSame('sam@example.com', $valid->checkout()->recipientEmail);
    }

    public function testCartLinePriceAndQuantityAreChecked(): void
    {
        $mapped = $this->mapper->map($this->payload([
            'cart' => [['sku' => '', 'price' => -1, 'qty' => 0]],
        ]));

        $this->assertSame([
            'cart[0].sku' => 'Sku is required.',
            'cart[0].price' => 'Price must be zero or greater.',
            'cart[0].qty' => 'Quantity must be at least 1.',
        ], $mapped->errors);
    }

    public function testBlankMobileIsIgnoredAndANumberIsRejected(): void
    {
        $blank = $this->mapper->map($this->payload(['mobile' => '']));
        $number = $this->mapper->map($this->payload(['mobile' => 7123456789]));

        $this->assertSame([], $blank->errors);
        $this->assertNull($blank->checkout()->mobile);
        $this->assertSame(['mobile' => 'Mobile must be a string.'], $number->errors);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'jo@example.com',
            'customerType' => 'regular',
            'cart' => [
                ['sku' => 'MUG-01', 'price' => 10.00, 'qty' => 2],
                ['sku' => 'TEA-01', 'price' => 5.00, 'qty' => 1],
            ],
        ], $overrides);
    }
}
