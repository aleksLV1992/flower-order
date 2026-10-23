<?php

declare(strict_types=1);

namespace App\Domain\Order;

class OrderItem
{
    public private(set) ?int $id = null;

    public function __construct(
        public private(set) Order $order,
        public private(set) int $position,
        public private(set) string $name,
        public private(set) int $qty,
        public private(set) int $unitPrice,
        public private(set) ?string $sku,
    ) {
    }

    public int $lineTotal {
        get => $this->qty * $this->unitPrice;
    }
}
