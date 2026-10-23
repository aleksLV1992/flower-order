<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Rule;

use App\Domain\Delivery\DeliveryCharge;
use App\Domain\Delivery\DeliveryContext;

interface DeliveryRule
{
    public function apply(DeliveryContext $context): ?DeliveryCharge;
}
