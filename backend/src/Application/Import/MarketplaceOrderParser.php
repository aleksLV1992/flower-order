<?php

declare(strict_types=1);

namespace App\Application\Import;

interface MarketplaceOrderParser
{
    public function extractId(mixed $raw): ?string;

    /**
     * @throws OrderRejected
     */
    public function parse(mixed $raw): ParsedOrder;
}
