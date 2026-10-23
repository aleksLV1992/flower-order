<?php

declare(strict_types=1);

namespace App\Application\Order\View;

final readonly class OrderItemView
{
    public function __construct(
        public ?string $sku,
        public string $name,
        public int $qty,
        public int $unitPrice,
        public int $lineTotal,
    ) {
    }
}
