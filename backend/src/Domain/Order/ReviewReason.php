<?php

declare(strict_types=1);

namespace App\Domain\Order;

final readonly class ReviewReason
{
    public function __construct(
        public string $code,
        public string $message,
    ) {
    }
}
