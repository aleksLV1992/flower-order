<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

use App\Domain\Shop\Shop;

class DeliveryTariff
{
    public private(set) ?int $id = null;

    public function __construct(
        public private(set) Shop $shop,
        public private(set) string $district,
        public private(set) string $name,
        public private(set) int $basePrice,
        public private(set) ?int $freeFrom,
    ) {
    }

    public function toDistrictTariff(): DistrictTariff
    {
        return new DistrictTariff($this->district, $this->name, $this->basePrice, $this->freeFrom);
    }
}
