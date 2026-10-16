<?php
declare(strict_types=1);

namespace App\Service\Checkout\Event;

use App\Entity\Order;

final class OrderPlaced
{
    public function __construct(
        public readonly Order $order,
        /** Total told to the customer. Gift wrap is included here, and not yet on the saved order. */
        public readonly float $chargedTotal,
        public readonly string $purchaserEmail,
        public readonly ?string $mobile = null,
    ) {
    }

    public function confirmationText(): string
    {
        if ($this->order->isSample()) {
            $firstItem = $this->order->getItems()->first();
            $sku = $firstItem === false ? '' : $firstItem->getSku();

            return 'Your free sample of ' . $sku . ' is on its way!';
        }

        return 'Thanks for your order! Total: £' . number_format($this->chargedTotal, 2);
    }

    public function emailRecipient(): string
    {
        if ($this->order->isGift()) {
            return (string) $this->order->getRecipientEmail();
        }

        return $this->purchaserEmail;
    }
}
