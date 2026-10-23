<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace;

use App\Application\Import\MarketplaceStatus;
use App\Application\Import\ParsedOrder;
use App\Domain\Order\OrderLine;
use App\Domain\Order\OrderStatus;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Transform\MapCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Map(target: ParsedOrder::class)]
final readonly class MarketplaceOrder
{
    /**
     * @param list<MarketplaceItem>|null $items
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Не заполнено обязательное поле: идентификатор заказа', normalizer: 'trim', payload: ['code' => 'missing_field'])]
        #[Map(target: 'externalId', transform: [MarketplaceOrderTransform::class, 'id'])]
        public string|int|null $id = null,
        #[Assert\NotBlank(message: 'Не заполнено обязательное поле: статус', normalizer: 'trim', payload: ['code' => 'missing_field'])]
        #[Map(transform: [MarketplaceValues::class, 'status'])]
        public ?string $status = null,
        #[Map(target: 'placedAt', transform: [MarketplaceOrderTransform::class, 'placedAt'])]
        public ?string $createdAt = null,
        #[Assert\Valid]
        #[Map(target: 'customerName', transform: [MarketplaceOrderTransform::class, 'customerName'])]
        #[Map(target: 'customerPhone', transform: [MarketplaceOrderTransform::class, 'customerPhone'])]
        public MarketplaceCustomer $customer = new MarketplaceCustomer(),
        #[Assert\Valid]
        #[Map(target: 'deliveryType', transform: [MarketplaceOrderTransform::class, 'deliveryType'])]
        #[Map(target: 'district', transform: [MarketplaceOrderTransform::class, 'district'])]
        #[Map(target: 'address', transform: [MarketplaceOrderTransform::class, 'address'])]
        #[Map(target: 'intervalStart', transform: [MarketplaceOrderTransform::class, 'intervalStart'])]
        #[Map(target: 'intervalEnd', transform: [MarketplaceOrderTransform::class, 'intervalEnd'])]
        public MarketplaceDelivery $delivery = new MarketplaceDelivery(),
        #[Assert\NotBlank(message: 'В заказе нет ни одной позиции', payload: ['code' => 'no_items'])]
        #[Assert\Valid]
        #[Map(target: 'lines', transform: new MapCollection(targetClass: OrderLine::class))]
        public ?array $items = null,
        #[Map(target: 'declaredTotal', transform: [MarketplaceValues::class, 'nullableKopecks'])]
        #[Map(target: 'reviewReasons', transform: [MarketplaceOrderTransform::class, 'reviewReasons'])]
        public int|float|string|null $total = null,
    ) {
    }

    #[Assert\Callback(payload: ['code' => 'unknown_status'])]
    public function validateStatus(ExecutionContextInterface $context): void
    {
        if ($this->status !== null && trim($this->status) !== '' && !MarketplaceStatus::toDomain($this->status) instanceof OrderStatus) {
            $context->buildViolation(sprintf('Неизвестный статус «%s»', $this->status))->atPath('status')->addViolation();
        }
    }

    #[Assert\Callback(payload: ['code' => 'invalid_datetime'])]
    public function validateCreatedAt(ExecutionContextInterface $context): void
    {
        if ($this->createdAt !== null && !MarketplaceValues::dateTime($this->createdAt) instanceof \DateTimeImmutable) {
            $context->buildViolation(sprintf('Некорректная дата «%s» в поле created_at', $this->createdAt))->atPath('createdAt')->addViolation();
        }
    }

    #[Assert\Callback(payload: ['code' => 'invalid_payload'])]
    public function validateTotal(ExecutionContextInterface $context): void
    {
        if ($this->total !== null && !MarketplaceValues::isMoney($this->total)) {
            $context->buildViolation('Некорректная сумма в поле total')->atPath('total')->addViolation();
        }
    }
}
