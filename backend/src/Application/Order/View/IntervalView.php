<?php

declare(strict_types=1);

namespace App\Application\Order\View;

final readonly class IntervalView
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }
}
