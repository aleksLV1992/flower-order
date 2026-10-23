<?php

declare(strict_types=1);

namespace App\Application\Import\View;

final readonly class RejectionView
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $field,
    ) {
    }
}
