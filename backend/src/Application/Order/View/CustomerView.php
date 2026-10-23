<?php

declare(strict_types=1);

namespace App\Application\Order\View;

final readonly class CustomerView
{
    public function __construct(
        public string $name,
        public string $phone,
    ) {
    }
}
