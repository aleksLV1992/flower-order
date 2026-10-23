<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

final readonly class DeliveryContext
{
    public function __construct(
        public DistrictTariff $tariff,
        public int $itemsTotal,
        public ?\DateTimeImmutable $placedAt,
        public \DateTimeImmutable $intervalStart,
    ) {
    }
}
