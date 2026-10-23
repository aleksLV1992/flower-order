<?php

declare(strict_types=1);

namespace App\Application\Order\View;

use App\Application\View\NoteView;
use Symfony\Component\ObjectMapper\Attribute\Map;

final readonly class OrderView
{
    /**
     * @param list<OrderItemView> $items
     * @param list<NoteView>      $reviewReasons
     */
    public function __construct(
        public int $id,
        public string $externalId,
        #[Map(transform: [OrderViewTransform::class, 'enumValue'])]
        public string $status,
        #[Map(source: 'customerName', transform: [OrderViewTransform::class, 'customer'])]
        public CustomerView $customer,
        #[Map(transform: [OrderViewTransform::class, 'enumValue'])]
        public string $deliveryType,
        public ?string $district,
        public ?string $address,
        #[Map(source: 'intervalStart', transform: [OrderViewTransform::class, 'interval'])]
        public ?IntervalView $interval,
        public ?\DateTimeImmutable $placedAt,
        #[Map(transform: [OrderViewTransform::class, 'items'])]
        public array $items,
        public int $itemsTotal,
        public ?int $declaredTotal,
        public bool $needsReview,
        #[Map(transform: [OrderViewTransform::class, 'notes'])]
        public array $reviewReasons,
        #[Map(transform: [OrderViewTransform::class, 'delivery'])]
        public DeliveryView $delivery,
        public int $grandTotal,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
