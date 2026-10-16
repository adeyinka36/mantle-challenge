<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CheckoutMapper
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function map(array $payload): CheckoutMapping
    {
        $request = $this->requestFrom($payload);
        $errors = $this->errorsFrom($this->validator->validate($request));

        if ($errors !== []) {
            return new CheckoutMapping(null, $errors);
        }

        return new CheckoutMapping($this->checkoutFrom($request), []);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requestFrom(array $payload): CheckoutRequest
    {
        $request = new CheckoutRequest();
        $request->email = is_string($payload['email'] ?? null) ? $payload['email'] : null;
        $request->customerType = $this->customerTypeFrom($payload);
        $request->cart = $this->cartFrom($payload['cart'] ?? null);
        $request->gift = $this->boolFrom($payload['gift'] ?? false);
        $request->recipientEmail = is_string($payload['recipientEmail'] ?? null) ? $payload['recipientEmail'] : null;
        $request->sample = $this->boolFrom($payload['sample'] ?? false);
        $request->mobile = $this->mobileFrom($payload);

        return $request;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function customerTypeFrom(array $payload): string
    {
        if (!array_key_exists('customerType', $payload) || $payload['customerType'] === null) {
            return 'regular';
        }

        return is_string($payload['customerType']) ? $payload['customerType'] : '';
    }

    /**
     * @return list<CartLineRequest>
     */
    private function cartFrom(mixed $cart): array
    {
        if (!is_array($cart)) {
            return [];
        }

        $lines = [];
        foreach ($cart as $line) {
            $line = is_array($line) ? $line : [];
            $lines[] = new CartLineRequest(
                sku: is_string($line['sku'] ?? null) ? $line['sku'] : '',
                price: $line['price'] ?? null,
                qty: $line['qty'] ?? null,
            );
        }

        return $lines;
    }

    private function boolFrom(mixed $value): mixed
    {
        if ($value === null || is_bool($value)) {
            return (bool) $value;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function mobileFrom(array $payload): mixed
    {
        if (!array_key_exists('mobile', $payload) || $payload['mobile'] === null || $payload['mobile'] === '') {
            return null;
        }

        return $payload['mobile'];
    }

    /**
     * @return array<string, string>
     */
    private function errorsFrom(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath() !== '' ? $violation->getPropertyPath() : 'payload';
            $errors[$field] ??= (string) $violation->getMessage();
        }

        return $errors;
    }

    private function checkoutFrom(CheckoutRequest $request): PlaceOrderData
    {
        $cart = [];
        foreach ($request->cart as $line) {
            $cart[] = new CartLine($line->sku, (float) $line->price, (int) $line->qty);
        }

        return new PlaceOrderData(
            email: trim((string) $request->email),
            customerType: $request->customerType,
            cart: $cart,
            gift: $request->gift === true,
            recipientEmail: $request->gift === true ? trim((string) $request->recipientEmail) : null,
            sample: $request->sample === true,
            mobile: is_string($request->mobile) ? $request->mobile : null,
        );
    }
}
