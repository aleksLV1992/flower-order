<?php

declare(strict_types=1);

namespace App\Application\Order\View;

final readonly class ShopView
{
    public function __construct(
        public int $id,
        public string $name,
        public string $timezone,
    ) {
    }
}
