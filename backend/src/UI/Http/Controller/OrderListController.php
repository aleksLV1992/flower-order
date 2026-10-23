<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Order\ListOrders;
use App\Application\Order\ListOrdersQuery;
use App\UI\Request\OrderListRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/shops/{shopId<\d+>}/orders', name: 'api_orders', methods: ['GET'])]
final class OrderListController extends AbstractController
{
    public function __construct(private readonly ListOrders $listOrders)
    {
    }

    public function __invoke(
        int $shopId,
        #[MapQueryString(validationFailedStatusCode: 400)]
        OrderListRequest $request = new OrderListRequest(),
    ): JsonResponse {
        return $this->json(($this->listOrders)(new ListOrdersQuery($shopId, $request->status, $request->page, $request->perPage)));
    }
}
