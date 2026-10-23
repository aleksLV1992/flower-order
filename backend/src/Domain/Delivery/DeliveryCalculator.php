<?php

declare(strict_types=1);

namespace App\Domain\Delivery;

use App\Domain\Delivery\Rule\DeliveryRule;
use App\Domain\Order\DeliveryType;

final readonly class DeliveryCalculator
{
    /**
     * @param iterable<DeliveryRule> $rules
     */
    public function __construct(private iterable $rules)
    {
    }

    /**
     * @throws UnknownDistrict
     */
    #[\NoDiscard]
    public function quote(DeliveryRequest $request, TariffBook $tariffs): DeliveryQuote
    {
        if ($request->type === DeliveryType::Pickup) {
            return DeliveryQuote::free();
        }

        $district = (string) $request->district;
        $tariff = $tariffs->forDistrict($district) ?? throw new UnknownDistrict($district);
        $context = new DeliveryContext($tariff, $request->itemsTotal, $request->placedAt, $request->intervalStart ?? throw new \LogicException('No interval'));

        $charges = [];
        foreach ($this->rules as $rule) {
            $charge = $rule->apply($context);
            if ($charge !== null) {
                $charges[] = $charge;
            }
        }

        return new DeliveryQuote($charges);
    }
}
