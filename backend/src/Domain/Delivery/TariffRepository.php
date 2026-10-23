<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

use App\Domain\Shop\Shop;

interface TariffRepository
{
    public function bookFor(Shop $shop): TariffBook;
}
