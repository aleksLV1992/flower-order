<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

final class UnknownDistrict extends \DomainException
{
    public function __construct(public readonly string $district)
    {
        parent::__construct(sprintf('Для района «%s» нет тарифа доставки', $district));
    }
}
