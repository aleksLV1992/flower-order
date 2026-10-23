<?php

declare(strict_types=1);

namespace App\Application\Order\View;

final readonly class OrderPageView
{
    /**
     * @param list<OrderView>    $items
     * @param array<string, int> $statusCounts
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $perPage,
        public int $total,
        public array $statusCounts,
        public ShopView $shop,
    ) {
    }
}
