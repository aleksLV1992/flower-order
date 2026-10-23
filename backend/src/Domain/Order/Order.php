<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Delivery\DeliveryCharge;
use App\Domain\Delivery\DeliveryQuote;
use App\Domain\Shop\Shop;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Order
{
    public private(set) ?int $id = null;

    /** @var list<array{code: string, message: string}> */
    private array $reviewReasonRecords;

    /** @var list<array{code: string, amount: int, label: string}> */
    private array $deliveryChargeRecords;

    /** @var Collection<int, OrderItem> */
    private Collection $itemCollection;

    /** @var list<OrderItem> */
    public array $items {
        get {
            $items = $this->itemCollection->toArray();
            usort($items, static fn (OrderItem $a, OrderItem $b): int => $a->position <=> $b->position);

            return $items;
        }
    }

    /** @var list<ReviewReason> */
    public array $reviewReasons {
        get => array_map(static fn (array $r): ReviewReason => new ReviewReason($r['code'], $r['message']), $this->reviewReasonRecords);
    }

    public DeliveryQuote $delivery {
        get => new DeliveryQuote(array_map(
            static fn (array $c): DeliveryCharge => new DeliveryCharge($c['code'], $c['amount'], $c['label']),
            $this->deliveryChargeRecords,
        ));
    }

    public private(set) int $itemsTotal;
    public private(set) int $deliveryCost;
    public private(set) int $grandTotal;
    public private(set) bool $needsReview;
    public private(set) \DateTimeImmutable $updatedAt;

    /**
     * @param non-empty-list<OrderLine> $lines
     * @param list<ReviewReason>        $reviewReasons
     */
    private function __construct(
        public private(set) Shop $shop,
        public private(set) string $externalId,
        public private(set) OrderStatus $status,
        public private(set) string $customerName,
        public private(set) PhoneNumber $customerPhone,
        public private(set) DeliveryType $deliveryType,
        public private(set) ?string $district,
        public private(set) ?string $address,
        public private(set) ?\DateTimeImmutable $intervalStart,
        public private(set) ?\DateTimeImmutable $intervalEnd,
        public private(set) ?\DateTimeImmutable $placedAt,
        public private(set) ?int $declaredTotal,
        array $lines,
        array $reviewReasons,
        DeliveryQuote $delivery,
        public private(set) \DateTimeImmutable $createdAt,
    ) {
        $this->itemCollection = new ArrayCollection();
        foreach ($lines as $position => $line) {
            $this->itemCollection->add(new OrderItem($this, $position, $line->name, $line->qty, $line->unitPrice, $line->sku));
        }
        $this->itemsTotal = array_sum(array_map(static fn (OrderLine $l): int => $l->total(), $lines));
        $this->reviewReasonRecords = array_map(static fn (ReviewReason $r): array => ['code' => $r->code, 'message' => $r->message], $reviewReasons);
        $this->needsReview = $reviewReasons !== [];
        $this->deliveryCost = $delivery->total();
        $this->deliveryChargeRecords = array_map(static fn (DeliveryCharge $c): array => ['code' => $c->code, 'amount' => $c->amount, 'label' => $c->label], $delivery->charges);
        $this->grandTotal = $this->itemsTotal + $this->deliveryCost;
        $this->updatedAt = $this->createdAt;
    }

    /**
     * @param non-empty-list<OrderLine> $lines
     * @param list<ReviewReason>        $reviewReasons
     */
    public static function place(
        Shop $shop,
        string $externalId,
        OrderStatus $status,
        string $customerName,
        PhoneNumber $customerPhone,
        DeliveryType $deliveryType,
        ?string $district,
        ?string $address,
        ?\DateTimeImmutable $intervalStart,
        ?\DateTimeImmutable $intervalEnd,
        ?\DateTimeImmutable $placedAt,
        ?int $declaredTotal,
        array $lines,
        array $reviewReasons,
        DeliveryQuote $delivery,
        \DateTimeImmutable $now,
    ): self {
        return new self(
            $shop,
            $externalId,
            $status,
            $customerName,
            $customerPhone,
            $deliveryType,
            $district,
            $address,
            $intervalStart,
            $intervalEnd,
            $placedAt,
            $declaredTotal,
            $lines,
            $reviewReasons,
            $delivery,
            $now,
        );
    }

    #[\NoDiscard]
    public function applyReportedStatus(OrderStatus $reported, \DateTimeImmutable $now): StatusChange
    {
        if ($reported === $this->status) {
            return StatusChange::Same;
        }
        if ($this->status->isFinal()) {
            return StatusChange::FinalKept;
        }
        $this->status = $reported;
        $this->updatedAt = $now;

        return StatusChange::Changed;
    }
}
