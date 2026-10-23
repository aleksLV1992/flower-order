<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Rule;

use App\Domain\Delivery\DeliveryCharge;
use App\Domain\Delivery\DeliveryContext;

final readonly class UrgencySurchargeRule implements DeliveryRule
{
    public function __construct(
        private int $fee,
        private int $windowMinutes,
    ) {
    }

    #[\Override]
    public function apply(DeliveryContext $context): ?DeliveryCharge
    {
        if (!$context->placedAt instanceof \DateTimeImmutable) {
            return null;
        }
        $leadSeconds = $context->intervalStart->getTimestamp() - $context->placedAt->getTimestamp();

        return $leadSeconds < $this->windowMinutes * 60
            ? new DeliveryCharge('urgency', $this->fee, 'Срочная доставка')
            : null;
    }
}
