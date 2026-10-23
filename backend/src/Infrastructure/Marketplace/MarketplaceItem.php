<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace;

use App\Domain\Order\OrderLine;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Map(target: OrderLine::class)]
final readonly class MarketplaceItem
{
    public function __construct(
        #[Assert\NotBlank(message: 'У позиции нет названия', normalizer: 'trim', payload: ['code' => 'invalid_item'])]
        #[Map(transform: [MarketplaceValues::class, 'text'])]
        public ?string $name = null,
        #[Assert\NotNull(message: 'Не указано количество позиции', payload: ['code' => 'invalid_item'])]
        #[Assert\Sequentially([
            new Assert\Type(type: 'integer', message: 'Количество позиции должно быть целым > 0', payload: ['code' => 'invalid_item']),
            new Assert\Positive(message: 'Количество позиции должно быть целым > 0', payload: ['code' => 'invalid_item']),
        ])]
        public int|float|null $qty = null,
        #[Assert\NotNull(message: 'Не указана цена позиции', payload: ['code' => 'invalid_item'])]
        #[Map(target: 'unitPrice', transform: [MarketplaceValues::class, 'kopecks'])]
        public int|float|string|null $price = null,
        #[Map(transform: [MarketplaceValues::class, 'optionalText'])]
        public ?string $sku = null,
    ) {
    }

    #[Assert\Callback(payload: ['code' => 'invalid_item'])]
    public function validatePrice(ExecutionContextInterface $context): void
    {
        if ($this->price !== null && !MarketplaceValues::isMoney($this->price)) {
            $context->buildViolation('Цена позиции должна быть неотрицательной суммой в рублях с точностью до копеек')->atPath('price')->addViolation();
        }
    }
}
