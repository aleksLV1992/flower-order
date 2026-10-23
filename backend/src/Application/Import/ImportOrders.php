<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Application\Exception\UnknownShop;
use App\Application\Import\View\ImportReportView;
use App\Application\Import\View\ImportResultView;
use App\Application\Import\View\ImportSummaryView;
use App\Application\Port\TransactionManager;
use App\Application\View\NoteView;
use App\Domain\Delivery\DeliveryCalculator;
use App\Domain\Delivery\TariffBook;
use App\Domain\Delivery\TariffRepository;
use App\Domain\Delivery\UnknownDistrict;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\ReviewReason;
use App\Domain\Order\StatusChange;
use App\Domain\Shop\Shop;
use App\Domain\Shop\ShopNotFound;
use App\Domain\Shop\ShopRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ImportOrders
{
    public function __construct(
        private TransactionManager $transactions,
        private ShopRepository $shops,
        private OrderRepository $orders,
        private TariffRepository $tariffs,
        private MarketplaceOrderParser $parser,
        private DeliveryCalculator $delivery,
        private ClockInterface $clock,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @throws UnknownShop
     */
    public function __invoke(ImportOrdersCommand $command): ImportReportView
    {
        /** @var array<int, ImportResult> $results */
        $results = [];
        /** @var array<int, ParsedOrder> $parsed */
        $parsed = [];
        /** @var array<string, int> $firstIndex */
        $firstIndex = [];

        foreach ($command->records as $index => $record) {
            $id = $this->parser->extractId($record);
            if ($id !== null && isset($firstIndex[$id])) {
                $results[$index] = ImportResult::duplicate($index, $id, $firstIndex[$id]);
                continue;
            }
            if ($id !== null) {
                $firstIndex[$id] = $index;
            }
            try {
                $parsed[$index] = $this->parser->parse($record);
            } catch (OrderRejected $e) {
                $results[$index] = ImportResult::rejected($index, $id, $e);
            }
        }

        $shopId = $command->shopId;
        $results += $this->transactions->transactional(function () use ($shopId, $parsed): array {
            try {
                $shop = $this->shops->getForUpdate($shopId);
            } catch (ShopNotFound $e) {
                throw UnknownShop::withId($shopId, $e);
            }
            $tariffs = $this->tariffs->bookFor($shop);
            $existing = $this->orders->findByExternalIds($shop, array_values(array_map(
                static fn (ParsedOrder $p): string => $p->externalId,
                $parsed,
            )));
            $now = $this->clock->now()->setTimezone(new \DateTimeZone('UTC'));

            $stored = [];
            foreach ($parsed as $index => $order) {
                $current = $existing[$order->externalId] ?? null;
                $stored[$index] = $current === null
                    ? $this->create($index, $shop, $order, $tariffs, $now)
                    : $this->reimport($index, $current, $order, $now);
            }

            return $stored;
        });

        ksort($results);

        return $this->report(array_values($results));
    }

    /**
     * @param list<ImportResult> $results
     */
    private function report(array $results): ImportReportView
    {
        $count = static fn (ImportOutcome $o): int => count(array_filter($results, static fn (ImportResult $r): bool => $r->outcome === $o));

        return new ImportReportView(
            summary: new ImportSummaryView(
                total: count($results),
                created: $count(ImportOutcome::Created),
                updated: $count(ImportOutcome::Updated),
                unchanged: $count(ImportOutcome::Unchanged),
                duplicate: $count(ImportOutcome::Duplicate),
                rejected: $count(ImportOutcome::Rejected),
            ),
            results: array_map(fn (ImportResult $r): ImportResultView => $this->mapper->map($r, ImportResultView::class), $results),
        );
    }

    private function create(int $index, Shop $shop, ParsedOrder $p, TariffBook $tariffs, \DateTimeImmutable $now): ImportResult
    {
        try {
            $quote = $this->delivery->quote($p->deliveryRequest(), $tariffs);
        } catch (UnknownDistrict $e) {
            return ImportResult::rejected($index, $p->externalId, new OrderRejected(RejectionCode::UnknownDistrict, $e->getMessage(), 'delivery.district'));
        }

        $order = Order::place(
            shop: $shop,
            externalId: $p->externalId,
            status: $p->status,
            customerName: $p->customerName,
            customerPhone: $p->customerPhone,
            deliveryType: $p->deliveryType,
            district: $p->district,
            address: $p->address,
            intervalStart: $p->intervalStart,
            intervalEnd: $p->intervalEnd,
            placedAt: $p->placedAt,
            declaredTotal: $p->declaredTotal,
            lines: $p->lines,
            reviewReasons: $p->reviewReasons,
            delivery: $quote,
            now: $now,
        );
        $this->orders->add($order);

        $notes = array_map(static fn (ReviewReason $r): NoteView => new NoteView($r->code, $r->message), $p->reviewReasons);

        return ImportResult::of($index, ImportOutcome::Created, $order, $notes);
    }

    private function reimport(int $index, Order $order, ParsedOrder $p, \DateTimeImmutable $now): ImportResult
    {
        $previous = $order->status;

        return match ($order->applyReportedStatus($p->status, $now)) {
            StatusChange::Changed => ImportResult::of($index, ImportOutcome::Updated, $order, previousStatus: $previous),
            StatusChange::Same => ImportResult::of($index, ImportOutcome::Unchanged, $order),
            StatusChange::FinalKept => ImportResult::of($index, ImportOutcome::Unchanged, $order, [new NoteView(
                'final_status_kept',
                sprintf('Статус «%s» финальный, площадка прислала «%s» — не меняем', $previous->value, $p->status->value),
            )]),
        };
    }
}
