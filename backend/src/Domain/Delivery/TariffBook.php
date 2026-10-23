<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

final readonly class TariffBook
{
    /** @var array<string, DistrictTariff> */
    private array $byDistrict;

    /**
     * @param iterable<DistrictTariff> $tariffs
     */
    public function __construct(iterable $tariffs)
    {
        $map = [];
        foreach ($tariffs as $t) {
            $map[$t->district] = $t;
        }
        $this->byDistrict = $map;
    }

    public function forDistrict(string $district): ?DistrictTariff
    {
        return $this->byDistrict[$district] ?? null;
    }
}
