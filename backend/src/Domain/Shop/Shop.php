<?php

declare(strict_types=1);

namespace App\Domain\Shop;

class Shop
{
    public private(set) ?int $id = null;

    public function __construct(
        public private(set) string $name,
        public private(set) string $timezone = 'Europe/Moscow',
    ) {
    }
}
