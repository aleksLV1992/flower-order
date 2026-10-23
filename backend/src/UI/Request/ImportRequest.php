<?php

declare(strict_types=1);

namespace App\UI\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ImportRequest
{
    public const int MAX_ORDERS = 1000;

    /**
     * @param list<mixed>|null $orders
     */
    public function __construct(
        #[Assert\NotNull(message: 'Ожидается объект {"orders": [...]}', payload: ['code' => 'invalid_batch'])]
        #[Assert\Count(max: self::MAX_ORDERS, maxMessage: 'В одной пачке не больше {{ limit }} заказов', payload: ['code' => 'batch_too_large'])]
        public ?array $orders = null,
    ) {
    }
}
