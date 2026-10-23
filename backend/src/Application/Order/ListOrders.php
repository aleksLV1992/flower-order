<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Application\Exception\UnknownShop;
use App\Application\Order\View\OrderPageView;
use App\Application\Order\View\OrderView;
use App\Application\Order\View\ShopView;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\OrderStatus;
use App\Domain\Shop\ShopNotFound;
use App\Domain\Shop\ShopRepository;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ListOrders
{
    public function __construct(
        private ShopRepository $shops,
        private OrderRepository $orders,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @throws UnknownShop
     */
    public function __invoke(ListOrdersQuery $query): OrderPageView
    {
        try {
            $shop = $this->shops->get($query->shopId);
        } catch (ShopNotFound $e) {
            throw UnknownShop::withId($query->shopId, $e);
        }
        $status = $query->status === null ? null : OrderStatus::from($query->status);
        $result = $this->orders->page($shop, $status, $query->page, $query->perPage);

        return new OrderPageView(
            items: array_map(fn (Order $o): OrderView => $this->mapper->map($o, OrderView::class), $result['items']),
            page: $query->page,
            perPage: $query->perPage,
            total: $result['total'],
            statusCounts: $this->orders->countByStatus($shop),
            shop: $this->mapper->map($shop, ShopView::class),
        );
    }
}
