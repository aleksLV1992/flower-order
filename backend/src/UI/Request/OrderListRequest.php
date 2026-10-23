<?php

declare(strict_types=1);

namespace App\UI\Request;

use App\Application\Order\ListOrdersQuery;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderListRequest
{
    public function __construct(
        #[Assert\Choice(callback: [ListOrdersQuery::class, 'statuses'], message: 'Неизвестный статус «{{ value }}»', payload: ['code' => 'invalid_query'])]
        public ?string $status = null,
        #[Assert\Positive(payload: ['code' => 'invalid_query'])]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 100, payload: ['code' => 'invalid_query'])]
        public int $perPage = 20,
    ) {
    }
}
