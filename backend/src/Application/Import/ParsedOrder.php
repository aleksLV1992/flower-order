<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Domain\Delivery\DeliveryRequest;
use App\Domain\Order\DeliveryType;
use App\Domain\Order\OrderLine;
use App\Domain\Order\OrderStatus;
use App\Domain\Order\PhoneNumber;
use App\Domain\Order\ReviewReason;

final readonly class ParsedOrder
{
    /**
     * @param non-empty-list<OrderLine> $lines
     * @param list<ReviewReason>        $reviewReasons
     */
    public function __construct(
        public string $externalId,
        public OrderStatus $status,
        public string $customerName,
        public PhoneNumber $customerPhone,
        public DeliveryType $deliveryType,
        public ?string $district,
        public ?string $address,
        public ?\DateTimeImmutable $intervalStart,
        public ?\DateTimeImmutable $intervalEnd,
        public ?\DateTimeImmutable $placedAt,
        public array $lines,
        public ?int $declaredTotal,
        public array $reviewReasons,
    ) {
    }

    public function itemsTotal(): int
    {
        return array_sum(array_map(static fn (OrderLine $l): int => $l->total(), $this->lines));
    }

    public function deliveryRequest(): DeliveryRequest
    {
        return new DeliveryRequest($this->deliveryType, $this->itemsTotal(), $this->district, $this->placedAt, $this->intervalStart);
    }
}
