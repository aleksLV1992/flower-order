<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Order\OrderStatus;

final readonly class ListOrdersQuery
{
    public function __construct(
        public int $shopId,
        public ?string $status = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return array_map(static fn (OrderStatus $s): string => $s->value, OrderStatus::cases());
    }
}
