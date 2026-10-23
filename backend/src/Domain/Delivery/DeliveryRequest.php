<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

use App\Domain\Order\DeliveryType;

final readonly class DeliveryRequest
{
    public function __construct(
        public DeliveryType $type,
        public int $itemsTotal,
        public ?string $district = null,
        public ?\DateTimeImmutable $placedAt = null,
        public ?\DateTimeImmutable $intervalStart = null,
    ) {
        if ($type === DeliveryType::Delivery && ($district === null || !$intervalStart instanceof \DateTimeImmutable)) {
            throw new \InvalidArgumentException('Delivery needs a district and an interval');
        }
    }
}
