<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Shop\Shop;

interface OrderRepository
{
    public function add(Order $order): void;

    /**
     * @param list<string> $externalIds
     *
     * @return array<string, Order>
     */
    public function findByExternalIds(Shop $shop, array $externalIds): array;

    /**
     * @return array{items: list<Order>, total: int}
     */
    public function page(Shop $shop, ?OrderStatus $status, int $page, int $perPage): array;

    /**
     * @return array<value-of<OrderStatus>, int>
     */
    public function countByStatus(Shop $shop): array;
}
