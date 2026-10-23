<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Import\ImportOrders;
use App\Application\Import\ImportOrdersCommand;
use App\UI\Request\ImportRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/shops/{shopId<\d+>}/imports', name: 'api_import', methods: ['POST'])]
final class ImportController extends AbstractController
{
    public function __construct(private readonly ImportOrders $importOrders)
    {
    }

    public function __invoke(int $shopId, #[MapRequestPayload] ImportRequest $request): JsonResponse
    {
        return $this->json(($this->importOrders)(new ImportOrdersCommand($shopId, $request->orders ?? [])));
    }
}
