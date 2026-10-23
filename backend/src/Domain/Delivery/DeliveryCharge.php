<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

final readonly class DeliveryCharge
{
    public function __construct(
        public string $code,
        public int $amount,
        public string $label,
    ) {
    }
}
