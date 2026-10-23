<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

final readonly class DeliveryQuote
{
    /**
     * @param list<DeliveryCharge> $charges
     */
    public function __construct(public array $charges)
    {
    }

    public static function free(): self
    {
        return new self([]);
    }

    public function total(): int
    {
        return array_sum(array_map(static fn (DeliveryCharge $c): int => $c->amount, $this->charges));
    }
}
