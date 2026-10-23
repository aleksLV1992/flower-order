<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Rule;

use App\Domain\Delivery\DeliveryCharge;
use App\Domain\Delivery\DeliveryContext;

final readonly class DistrictBasePriceRule implements DeliveryRule
{
    #[\Override]
    public function apply(DeliveryContext $context): DeliveryCharge
    {
        $tariff = $context->tariff;
        if ($tariff->freeFrom !== null && $context->itemsTotal >= $tariff->freeFrom) {
            return new DeliveryCharge('base', 0, sprintf('Доставка, район «%s»: бесплатно от порога суммы', $tariff->name));
        }

        return new DeliveryCharge('base', $tariff->basePrice, sprintf('Доставка, район «%s»', $tariff->name));
    }
}
