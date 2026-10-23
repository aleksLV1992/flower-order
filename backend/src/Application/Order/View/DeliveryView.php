<?php

declare(strict_types=1);

namespace App\Application\Order\View;

final readonly class DeliveryView
{
    /**
     * @param list<DeliveryChargeView> $breakdown
     */
    public function __construct(
        public int $cost,
        public array $breakdown,
    ) {
    }
}
