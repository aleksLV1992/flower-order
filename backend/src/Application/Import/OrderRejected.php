<?php

declare(strict_types=1);

namespace App\Application\Import;

final class OrderRejected extends \DomainException
{
    public function __construct(
        public readonly RejectionCode $reason,
        string $message,
        public readonly ?string $field = null,
    ) {
        parent::__construct($message);
    }
}
