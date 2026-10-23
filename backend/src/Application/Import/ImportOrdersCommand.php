<?php

declare(strict_types=1);

namespace App\Application\Import;

final readonly class ImportOrdersCommand
{
    /**
     * @param list<mixed> $records
     */
    public function __construct(
        public int $shopId,
        public array $records,
    ) {
    }
}
