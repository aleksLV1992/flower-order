<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

final readonly class DistrictTariff
{
    public function __construct(
        public string $district,
        public string $name,
        public int $basePrice,
        public ?int $freeFrom,
    ) {
    }
}
