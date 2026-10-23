<?php

declare(strict_types=1);

namespace App\Domain\Order;

final readonly class OrderLine
{
    public function __construct(
        public string $name,
        public int $qty,
        public int $unitPrice,
        public ?string $sku = null,
    ) {
        if ($qty <= 0 || $unitPrice < 0) {
            throw new \InvalidArgumentException('Quantity must be positive and price non-negative');
        }
    }

    public function total(): int
    {
        return $this->qty * $this->unitPrice;
    }
}
