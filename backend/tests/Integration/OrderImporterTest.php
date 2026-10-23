<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Exception\UnknownShop;
use App\Application\Import\ImportOrders;
use App\Application\Import\ImportOrdersCommand;
use App\Application\Import\View\ImportReportView;
use App\Application\Import\View\ImportSummaryView;
use App\Tests\Unit\OrderFactory;
use PHPUnit\Framework\Attributes\DataProvider;

final class OrderImporterTest extends DatabaseTestCase
{
    private ImportOrders $importer;

    protected function setUp(): void
    {
        self::bootKernel();
        self::resetOrders();
        $this->importer = self::getContainer()->get(ImportOrders::class);
    }

    /**
     * @param list<mixed> $records
     */
    private function import(array $records): ImportReportView
    {
        self::getContainer()->get('doctrine')->getManager()->clear();

        return ($this->importer)(new ImportOrdersCommand(1, $records));
    }

    /**
     * @return array<string, string>
     */
    private function outcomes(ImportReportView $report): array
    {
        $map = [];
        foreach ($report->results as $r) {
            $map[$r->index . ':' . $r->externalId] = $r->outcome . ($r->reason !== null ? '/' . $r->reason->code : '');
        }

        return $map;
    }

    public function testFixtureFirstImport(): void
    {
        $report = $this->import(self::fixture());

        $this->assertSame([
            '0:MP-1001' => 'created',
            '1:MP-1002' => 'created',
            '2:MP-1003' => 'created',
            '3:MP-1004' => 'created',
            '4:MP-1005' => 'created',
            '5:MP-1006' => 'rejected/unknown_district',
            '6:MP-1007' => 'rejected/invalid_phone',
            '7:MP-1008' => 'rejected/unknown_status',
            '8:MP-1009' => 'created',
            '9:MP-1001' => 'duplicate',
            '10:MP-1010' => 'created',
        ], $this->outcomes($report));
        $this->assertEquals(new ImportSummaryView(11, 7, 0, 0, 1, 3), $report->summary);

        $rows = self::db()->fetchAllAssociativeIndexed(
            'SELECT external_id, status, customer_phone, items_total, delivery_cost, grand_total, needs_review FROM orders ORDER BY external_id',
        );
        $this->assertSame([
            'MP-1001' => ['status' => 'new', 'customer_phone' => '+79001234567', 'items_total' => 320000, 'delivery_cost' => 30000, 'grand_total' => 350000, 'needs_review' => 0],
            'MP-1002' => ['status' => 'accepted', 'customer_phone' => '+79001112233', 'items_total' => 500000, 'delivery_cost' => 60000, 'grand_total' => 560000, 'needs_review' => 0],
            'MP-1003' => ['status' => 'new', 'customer_phone' => '+79005557788', 'items_total' => 640000, 'delivery_cost' => 0, 'grand_total' => 640000, 'needs_review' => 0],
            'MP-1004' => ['status' => 'new', 'customer_phone' => '+79004443322', 'items_total' => 150000, 'delivery_cost' => 0, 'grand_total' => 150000, 'needs_review' => 0],
            'MP-1005' => ['status' => 'delivered', 'customer_phone' => '+79001230000', 'items_total' => 280000, 'delivery_cost' => 40000, 'grand_total' => 320000, 'needs_review' => 0],
            'MP-1009' => ['status' => 'new', 'customer_phone' => '+79003334455', 'items_total' => 360000, 'delivery_cost' => 70000, 'grand_total' => 430000, 'needs_review' => 1],
            'MP-1010' => ['status' => 'cancelled', 'customer_phone' => '+79004445566', 'items_total' => 200000, 'delivery_cost' => 30000, 'grand_total' => 230000, 'needs_review' => 0],
        ], array_map($this->intColumns(...), $rows));

        $this->assertSame(7, (int) self::db()->fetchOne('SELECT COUNT(*) FROM order_item'));
        $this->assertSame('B-101', self::db()->fetchOne("SELECT i.sku FROM order_item i JOIN orders o ON o.id = i.order_id WHERE o.external_id = 'MP-1001'"));
    }

    /**
     * @param array<array-key, mixed> $row
     *
     * @return array<array-key, mixed>
     */
    private function intColumns(array $row): array
    {
        foreach ($row as $k => $v) {
            if (is_string($v) && ctype_digit($v)) {
                $row[$k] = (int) $v;
            }
        }

        return $row;
    }

    public function testDatetimesAreStoredInUtc(): void
    {
        $this->import(self::fixture());

        $this->assertSame(['placed_at' => '2026-10-08 06:00:00', 'interval_start' => '2026-10-10 11:00:00'], self::db()->fetchAssociative("SELECT placed_at, interval_start FROM orders WHERE external_id = 'MP-1001'"));
    }

    public function testRepeatedImportIsIdempotent(): void
    {
        $this->import(self::fixture());
        $report = $this->import(self::fixture());
        $this->assertEquals(new ImportSummaryView(11, 0, 0, 7, 1, 3), $report->summary);
        $this->assertSame(7, (int) self::db()->fetchOne('SELECT COUNT(*) FROM orders'));
        $this->assertSame(7, (int) self::db()->fetchOne('SELECT COUNT(*) FROM order_item'));
    }

    public function testReimportChangesOnlyStatus(): void
    {
        $this->import([OrderFactory::raw(['id' => 'X', 'status' => 'NEW'])]);
        $report = $this->import([OrderFactory::raw([
            'id' => 'X',
            'status' => 'ACCEPTED',
            'customer' => ['name' => 'Другое имя'],
            'delivery' => ['district' => 'suburb'],
            'items' => [['name' => 'Другое', 'qty' => 10, 'price' => 99999]],
            'total' => 999990,
        ])]);

        $r = $report->results[0];
        $this->assertSame(['updated', 'accepted', 'new'], [$r->outcome, $r->status, $r->previousStatus]);
        $this->assertSame(['status' => 'accepted', 'customer_name' => 'Анна', 'district' => 'centre', 'items_total' => 100000, 'delivery_cost' => 30000], $this->intColumns((array) self::db()->fetchAssociative("SELECT status, customer_name, district, items_total, delivery_cost FROM orders WHERE external_id = 'X'")));
        $this->assertSame(1, (int) self::db()->fetchOne('SELECT COUNT(*) FROM order_item'));
    }

    /**
     * @return iterable<array{string, string, string, string}>
     */
    public static function transitions(): iterable
    {
        yield 'new -> in delivery' => ['NEW', 'IN_DELIVERY', 'updated', 'delivering'];
        yield 'accepted -> done' => ['ACCEPTED', 'DONE', 'updated', 'delivered'];
        yield 'non-final may go back' => ['IN_DELIVERY', 'NEW', 'updated', 'new'];
        yield 'delivered is final' => ['DONE', 'NEW', 'unchanged', 'delivered'];
        yield 'delivered -> cancelled is blocked too' => ['DONE', 'CANCELED', 'unchanged', 'delivered'];
        yield 'cancelled is final' => ['CANCELED', 'ACCEPTED', 'unchanged', 'cancelled'];
        yield 'same status' => ['ACCEPTED', 'ACCEPTED', 'unchanged', 'accepted'];
    }

    #[DataProvider('transitions')]
    public function testStatusTransitions(string $first, string $second, string $outcome, string $finalStatus): void
    {
        $this->import([OrderFactory::raw(['id' => 'T', 'status' => $first])]);
        $r = $this->import([OrderFactory::raw(['id' => 'T', 'status' => $second])])->results[0];

        $this->assertSame($outcome, $r->outcome);
        $this->assertSame($finalStatus, self::db()->fetchOne("SELECT status FROM orders WHERE external_id = 'T'"));
        if ($first === 'DONE' || $first === 'CANCELED') {
            $this->assertSame('final_status_kept', $r->notes[0]->code ?? null);
        }
    }

    public function testDuplicateInBatchWhenFirstOccurrenceIsRejected(): void
    {
        $report = $this->import([
            OrderFactory::raw(['id' => 'D', 'status' => 'PAID']),
            OrderFactory::raw(['id' => 'D', 'status' => 'NEW']),
        ]);

        $this->assertSame(['0:D' => 'rejected/unknown_status', '1:D' => 'duplicate'], $this->outcomes($report));
        $this->assertSame(0, (int) self::db()->fetchOne('SELECT COUNT(*) FROM orders'));
    }

    public function testDuplicateDetectionNormalizesId(): void
    {
        $report = $this->import([OrderFactory::raw(['id' => 77]), OrderFactory::raw(['id' => ' 77 '])]);

        $this->assertSame(['0:77' => 'created', '1:77' => 'duplicate'], $this->outcomes($report));
    }

    public function testExternalIdIsCaseSensitive(): void
    {
        $report = $this->import([OrderFactory::raw(['id' => 'MP-1']), OrderFactory::raw(['id' => 'mp-1'])]);

        $this->assertSame(['0:MP-1' => 'created', '1:mp-1' => 'created'], $this->outcomes($report));
        $this->assertSame(2, (int) self::db()->fetchOne('SELECT COUNT(*) FROM orders'));
        $repeat = $this->import([OrderFactory::raw(['id' => 'MP-1']), OrderFactory::raw(['id' => 'mp-1'])]);
        $this->assertSame([0, 2], [$repeat->summary->created, $repeat->summary->unchanged]);
    }

    public function testInvalidRecordForExistingOrderDoesNotTouchIt(): void
    {
        $this->import([OrderFactory::raw(['id' => 'E', 'status' => 'NEW'])]);
        $report = $this->import([OrderFactory::raw(['id' => 'E', 'status' => 'DONE', 'customer' => ['phone' => '123']])]);

        $this->assertSame(['0:E' => 'rejected/invalid_phone'], $this->outcomes($report));
        $this->assertSame('new', self::db()->fetchOne("SELECT status FROM orders WHERE external_id = 'E'"));
    }

    public function testExistingOrderStatusUpdateDoesNotNeedTariff(): void
    {
        $this->import([OrderFactory::raw(['id' => 'Z', 'status' => 'NEW'])]);
        self::db()->executeStatement("UPDATE orders SET district = 'east' WHERE external_id = 'Z'");
        $report = $this->import([OrderFactory::raw(['id' => 'Z', 'status' => 'ACCEPTED', 'delivery' => ['district' => 'east']])]);

        $this->assertSame(['0:Z' => 'updated'], $this->outcomes($report));
    }

    public function testGarbageRecordsDoNotBreakTheBatch(): void
    {
        $report = $this->import(['oops', 42, null, [], OrderFactory::raw(['id' => 'OK'])]);
        $this->assertEquals(new ImportSummaryView(5, 1, 0, 0, 0, 4), $report->summary);
    }

    public function testUnknownShop(): void
    {
        $this->expectException(UnknownShop::class);
        ($this->importer)(new ImportOrdersCommand(999, [OrderFactory::raw()]));
    }
}
