<?php

declare(strict_types=1);

namespace App\Application\Order\View;

final readonly class DeliveryChargeView
{
    public function __construct(
        public string $code,
        public string $label,
        public int $amount,
    ) {
    }
}
