<?php

declare(strict_types=1);

namespace App\Application\Import\View;

use App\Application\Import\OrderRejected;
use App\Domain\Order\Order;

final class ImportViewTransform
{
    public static function enumValue(?\BackedEnum $value): string|int|null
    {
        return $value?->value;
    }

    public static function orderId(?Order $order): ?int
    {
        return $order?->id;
    }

    public static function orderStatus(?Order $order): ?string
    {
        return $order?->status->value;
    }

    public static function rejection(?OrderRejected $rejection): ?RejectionView
    {
        return $rejection instanceof OrderRejected ? new RejectionView($rejection->reason->value, $rejection->getMessage(), $rejection->field) : null;
    }
}
