<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Domain\Order\OrderStatus;

final class MarketplaceStatus
{
    private const array MAP = [
        'NEW' => OrderStatus::New,
        'ACCEPTED' => OrderStatus::Accepted,
        'IN_DELIVERY' => OrderStatus::Delivering,
        'DONE' => OrderStatus::Delivered,
        'CANCELED' => OrderStatus::Cancelled,
    ];

    public static function toDomain(string $raw): ?OrderStatus
    {
        return self::MAP[strtoupper(trim($raw))] ?? null;
    }
}
