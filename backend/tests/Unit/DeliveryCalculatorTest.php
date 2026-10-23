<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Import\MarketplaceOrderParser;
use App\Domain\Delivery\DeliveryCalculator;
use App\Domain\Delivery\DeliveryCharge;
use App\Domain\Delivery\DistrictTariff;
use App\Domain\Delivery\Rule\DistrictBasePriceRule;
use App\Domain\Delivery\Rule\UrgencySurchargeRule;
use App\Domain\Delivery\TariffBook;
use App\Domain\Delivery\UnknownDistrict;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeliveryCalculatorTest extends KernelTestCase
{
    private DeliveryCalculator $calculator;
    private TariffBook $tariffs;
    private MarketplaceOrderParser $parser;

    protected function setUp(): void
    {
        $this->calculator = new DeliveryCalculator([new DistrictBasePriceRule(), new UrgencySurchargeRule(20000, 120)]);
        $this->tariffs = new TariffBook([
            new DistrictTariff('centre', 'Центр', 30000, 500000),
            new DistrictTariff('north', 'Север', 40000, 700000),
            new DistrictTariff('south', 'Юг', 40000, 700000),
            new DistrictTariff('suburb', 'Пригород', 70000, null),
        ]);
        $this->parser = self::getContainer()->get(MarketplaceOrderParser::class);
    }

    /**
     * @return iterable<string, array{string, int, string, string, int, list<string>}>
     */
    public static function cases(): iterable
    {
        yield 'centre below threshold' => ['centre', 4999, '09:00', '14:00', 30000, ['base']];
        yield 'centre exactly at threshold is free' => ['centre', 5000, '09:00', '14:00', 0, ['base']];
        yield 'north above threshold' => ['north', 7500, '09:00', '14:00', 0, ['base']];
        yield 'south below threshold' => ['south', 6999, '09:00', '14:00', 40000, ['base']];
        yield 'suburb is never free' => ['suburb', 100000, '09:00', '14:00', 70000, ['base']];
        yield 'urgent: 1h59m' => ['centre', 1000, '09:00', '10:59', 50000, ['base', 'urgency']];
        yield 'not urgent: exactly 2h' => ['centre', 1000, '09:00', '11:00', 30000, ['base']];
        yield 'urgent + free: free waives only base' => ['north', 7500, '11:00', '12:30', 20000, ['base', 'urgency']];
        yield 'interval already started at creation is urgent' => ['centre', 1000, '15:00', '14:00', 50000, ['base', 'urgency']];
        yield 'suburb urgent' => ['suburb', 1000, '09:00', '10:00', 90000, ['base', 'urgency']];
    }

    /**
     * @param list<string> $codes
     */
    #[DataProvider('cases')]
    public function testQuote(string $district, int $itemsRub, string $created, string $from, int $expected, array $codes): void
    {
        $order = $this->parser->parse(OrderFactory::raw([
            'created_at' => "2026-10-20T{$created}:00+03:00",
            'delivery' => ['district' => $district, 'time_from' => $from, 'time_to' => '23:00'],
            'items' => [['name' => 'Букет', 'qty' => 1, 'price' => $itemsRub]],
            'total' => $itemsRub,
        ]));

        $quote = $this->calculator->quote($order->deliveryRequest(), $this->tariffs);

        $this->assertSame($expected, $quote->total());
        $this->assertSame($codes, array_map(static fn (DeliveryCharge $c): string => $c->code, $quote->charges));
    }

    public function testUrgencyComparesAbsoluteTimeAcrossOffsets(): void
    {
        $order = $this->parser->parse(OrderFactory::raw([
            'created_at' => '2026-10-20T06:00:00Z',
            'delivery' => ['time_from' => '10:30', 'time_to' => '12:00'],
        ]));

        $this->assertSame(50000, $this->calculator->quote($order->deliveryRequest(), $this->tariffs)->total());
    }

    public function testUrgencyAcrossMidnight(): void
    {
        $order = $this->parser->parse(OrderFactory::raw([
            'created_at' => '2026-10-19T23:30:00+03:00',
            'delivery' => ['date' => '2026-10-20', 'time_from' => '01:00', 'time_to' => '03:00'],
        ]));

        $this->assertSame(50000, $this->calculator->quote($order->deliveryRequest(), $this->tariffs)->total());
    }

    public function testThresholdUsesItemsSumNotDeclaredTotal(): void
    {
        $order = $this->parser->parse(OrderFactory::raw([
            'items' => [['name' => 'Букет', 'qty' => 1, 'price' => 4000]],
            'total' => 6000,
        ]));

        $this->assertSame(30000, $this->calculator->quote($order->deliveryRequest(), $this->tariffs)->total());
    }

    public function testPickupIsFree(): void
    {
        $order = $this->parser->parse(OrderFactory::raw(['delivery' => ['type' => 'pickup', 'district' => 'nowhere']]));

        $this->assertSame(0, $this->calculator->quote($order->deliveryRequest(), $this->tariffs)->total());
    }

    public function testNoUrgencyWithoutCreatedAt(): void
    {
        $order = $this->parser->parse(OrderFactory::raw([
            'created_at' => null,
            'delivery' => ['time_from' => '09:30'],
        ]));

        $this->assertSame(30000, $this->calculator->quote($order->deliveryRequest(), $this->tariffs)->total());
    }

    public function testUnknownDistrictIsRejected(): void
    {
        $order = $this->parser->parse(OrderFactory::raw(['delivery' => ['district' => 'east']]));

        $this->expectExceptionObject(new UnknownDistrict('east'));
        (void) $this->calculator->quote($order->deliveryRequest(), $this->tariffs);
    }
}
