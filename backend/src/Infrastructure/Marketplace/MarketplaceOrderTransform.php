<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace;

use App\Domain\Order\DeliveryType;
use App\Domain\Order\PhoneNumber;
use App\Domain\Order\ReviewReason;

final class MarketplaceOrderTransform
{
    public static function id(string|int $id): string
    {
        return trim((string) $id);
    }

    public static function placedAt(?string $createdAt): ?\DateTimeImmutable
    {
        return $createdAt === null ? null : MarketplaceValues::dateTime($createdAt);
    }

    public static function customerName(MarketplaceCustomer $customer): string
    {
        return trim((string) $customer->name);
    }

    public static function customerPhone(MarketplaceCustomer $customer): PhoneNumber
    {
        return MarketplaceValues::phone((string) $customer->phone);
    }

    public static function deliveryType(MarketplaceDelivery $delivery): DeliveryType
    {
        return MarketplaceValues::deliveryType((string) $delivery->type) ?? throw new \LogicException('Delivery type must be validated before mapping');
    }

    public static function district(MarketplaceDelivery $delivery): ?string
    {
        return $delivery->isDelivery() ? strtolower(trim((string) $delivery->district)) : null;
    }

    public static function address(MarketplaceDelivery $delivery): ?string
    {
        return $delivery->isDelivery() ? trim((string) $delivery->address) : null;
    }

    public static function intervalStart(MarketplaceDelivery $delivery): ?\DateTimeImmutable
    {
        return $delivery->slotStart();
    }

    public static function intervalEnd(MarketplaceDelivery $delivery): ?\DateTimeImmutable
    {
        return $delivery->slotEnd();
    }

    /**
     * @return list<ReviewReason>
     */
    public static function reviewReasons(int|float|string|null $total, MarketplaceOrder $order): array
    {
        $reasons = [];
        if ($order->delivery->isDelivery() && $order->createdAt === null) {
            $reasons[] = new ReviewReason('created_at_missing', 'Нет времени создания заказа — срочность не определить, надбавка не начислена');
        }
        if ($total === null) {
            $reasons[] = new ReviewReason('total_missing', 'Площадка не передала итог заказа');

            return $reasons;
        }

        $itemsTotal = array_sum(array_map(
            static fn (MarketplaceItem $i): int => (int) $i->qty * MarketplaceValues::kopecks((string) $i->price),
            $order->items ?? [],
        ));
        $declared = MarketplaceValues::kopecks($total);
        if ($declared !== $itemsTotal) {
            $reasons[] = new ReviewReason('total_mismatch', sprintf(
                'Сумма позиций %s ₽ не совпадает с итогом площадки %s ₽; в расчётах — сумма позиций',
                MarketplaceValues::rub($itemsTotal),
                MarketplaceValues::rub($declared),
            ));
        }

        return $reasons;
    }
}
