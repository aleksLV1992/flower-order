<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Import\MarketplaceOrderParser;
use App\Application\Import\OrderRejected;
use App\Application\Import\ParsedOrder;
use App\Application\Import\RejectionCode;
use App\Domain\Order\DeliveryType;
use App\Domain\Order\OrderStatus;
use App\Domain\Order\ReviewReason;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MarketplaceOrderParserTest extends KernelTestCase
{
    private MarketplaceOrderParser $parser;

    protected function setUp(): void
    {
        $this->parser = self::getContainer()->get(MarketplaceOrderParser::class);
    }

    public function testValidDeliveryOrder(): void
    {
        $order = $this->parser->parse(OrderFactory::raw([
            'customer' => ['phone' => '8 (912) 345-67-89'],
            'items' => [['name' => 'Букет', 'qty' => 1, 'price' => 2500], ['name' => 'Открытка', 'qty' => 2, 'price' => 150]],
            'total' => 2800,
        ]));

        $this->assertSame('MP-1', $order->externalId);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame('+79123456789', $order->customerPhone->value);
        $this->assertSame(DeliveryType::Delivery, $order->deliveryType);
        $this->assertSame('centre', $order->district);
        $this->assertSame(280000, $order->itemsTotal());
        $this->assertSame(280000, $order->declaredTotal);
        $this->assertSame([], $this->reviewCodes($order));
    }

    /**
     * @return list<string>
     */
    private function reviewCodes(ParsedOrder $order): array
    {
        return array_map(static fn (ReviewReason $r): string => $r->code, $order->reviewReasons);
    }

    public function testDatetimesAreConvertedToUtc(): void
    {
        $order = $this->parser->parse(OrderFactory::raw());

        $this->assertSame('2026-10-20T06:00:00+00:00', $order->placedAt?->format(\DATE_ATOM));
        $this->assertSame('2026-10-20T11:00:00+00:00', $order->intervalStart?->format(\DATE_ATOM));
        $this->assertSame('2026-10-20T13:00:00+00:00', $order->intervalEnd?->format(\DATE_ATOM));
    }

    public function testCreatedAtOffsetIsRespected(): void
    {
        $this->assertSame('2026-10-20T09:00:00+00:00', $this->parser->parse(OrderFactory::raw(['created_at' => '2026-10-20T09:00:00Z']))->placedAt?->format(\DATE_ATOM));
        $this->assertSame('2026-10-20T04:00:00+00:00', $this->parser->parse(OrderFactory::raw(['created_at' => '2026-10-20T09:00:00+05:00']))->placedAt?->format(\DATE_ATOM));
    }

    public function testCreatedAtWithoutOffsetIsMoscow(): void
    {
        $this->assertSame('2026-10-20T06:00:00+00:00', $this->parser->parse(OrderFactory::raw(['created_at' => '2026-10-20T09:00:00']))->placedAt?->format(\DATE_ATOM));
    }

    public function testSingleDigitHourIsAccepted(): void
    {
        $this->assertSame('2026-10-20T06:00:00+00:00', $this->parser->parse(OrderFactory::raw(['delivery' => ['time_from' => '9:00']]))->intervalStart?->format(\DATE_ATOM));
    }

    public function testWrongJsonTypeIsRejectedWithField(): void
    {
        try {
            $this->parser->parse(OrderFactory::raw(['delivery' => ['address' => 12]]));
            $this->fail('Expected rejection');
        } catch (OrderRejected $e) {
            $this->assertSame([RejectionCode::InvalidPayload, 'delivery.address'], [$e->reason, $e->field]);
        }
    }

    public function testSkuIsKept(): void
    {
        $this->assertSame('B-1', $this->parser->parse(OrderFactory::raw())->lines[0]->sku);
        $this->assertNull($this->parser->parse(OrderFactory::raw(['items' => [['name' => 'A', 'qty' => 1, 'price' => 1]]]))->lines[0]->sku);
    }

    public function testPickupNeedsNoAddressDistrictOrInterval(): void
    {
        $order = $this->parser->parse(OrderFactory::raw(['delivery' => ['type' => 'pickup', 'district' => null, 'address' => null, 'date' => null, 'time_from' => null, 'time_to' => null]]));

        $this->assertSame(DeliveryType::Pickup, $order->deliveryType);
        $this->assertNull($order->district);
        $this->assertNull($order->intervalStart);
    }

    public function testNumericIdIsNormalizedToString(): void
    {
        $this->assertSame('42', $this->parser->parse(OrderFactory::raw(['id' => 42]))->externalId);
        $this->assertSame('MP-7', $this->parser->parse(OrderFactory::raw(['id' => '  MP-7 ']))->externalId);
    }

    public function testDistrictIsCaseInsensitive(): void
    {
        $this->assertSame('north', $this->parser->parse(OrderFactory::raw(['delivery' => ['district' => ' North ']]))->district);
    }

    public function testTotalMismatchFlagsReviewAndUsesItemsSum(): void
    {
        $order = $this->parser->parse(OrderFactory::raw([
            'items' => [['name' => 'Тюльпаны', 'qty' => 2, 'price' => 1990.5], ['name' => 'Упаковка', 'qty' => 1, 'price' => 300]],
            'total' => 4000,
        ]));

        $this->assertSame(428100, $order->itemsTotal());
        $this->assertSame(400000, $order->declaredTotal);
        $this->assertSame(['total_mismatch'], $this->reviewCodes($order));
    }

    public function testMissingTotalFlagsReview(): void
    {
        $order = $this->parser->parse(OrderFactory::raw(['total' => null]));

        $this->assertNull($order->declaredTotal);
        $this->assertSame(['total_missing'], $this->reviewCodes($order));
    }

    public function testMissingCreatedAtOnDeliveryFlagsReview(): void
    {
        $order = $this->parser->parse(OrderFactory::raw(['created_at' => null]));

        $this->assertNull($order->placedAt);
        $this->assertSame(['created_at_missing'], $this->reviewCodes($order));
    }

    public function testMoneyAsStringWithKopecks(): void
    {
        $order = $this->parser->parse(OrderFactory::raw([
            'items' => [['name' => 'A', 'qty' => 3, 'price' => '0.10']],
            'total' => '0.30',
        ]));

        $this->assertSame(30, $order->itemsTotal());
        $this->assertSame([], $this->reviewCodes($order));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, RejectionCode, string}>
     */
    public static function rejections(): iterable
    {
        yield 'no id' => [['id' => null], RejectionCode::MissingField, 'id'];
        yield 'blank id' => [['id' => '  '], RejectionCode::MissingField, 'id'];
        yield 'no status' => [['status' => null], RejectionCode::MissingField, 'status'];
        yield 'unknown status' => [['status' => 'PAID'], RejectionCode::UnknownStatus, 'status'];
        yield 'no customer name' => [['customer' => ['name' => '']], RejectionCode::MissingField, 'customer.name'];
        yield 'no customer at all' => [['customer' => null], RejectionCode::MissingField, 'customer.name'];
        yield 'no phone' => [['customer' => ['phone' => null]], RejectionCode::MissingField, 'customer.phone'];
        yield 'bad phone' => [['customer' => ['phone' => '12-34-56']], RejectionCode::InvalidPhone, 'customer.phone'];
        yield 'no items key' => [['items' => null], RejectionCode::NoItems, 'items'];
        yield 'empty items' => [['items' => []], RejectionCode::NoItems, 'items'];
        yield 'item without name' => [['items' => [['qty' => 1, 'price' => 10]]], RejectionCode::InvalidItem, 'items[0].name'];
        yield 'item zero qty' => [['items' => [['name' => 'A', 'qty' => 0, 'price' => 10]]], RejectionCode::InvalidItem, 'items[0].qty'];
        yield 'item fractional qty' => [['items' => [['name' => 'A', 'qty' => 1.5, 'price' => 10]]], RejectionCode::InvalidItem, 'items[0].qty'];
        yield 'item negative price' => [['items' => [['name' => 'A', 'qty' => 1, 'price' => -10]]], RejectionCode::InvalidItem, 'items[0].price'];
        yield 'item price with 3 decimals' => [['items' => [['name' => 'A', 'qty' => 1, 'price' => 10.555]]], RejectionCode::InvalidItem, 'items[0].price'];
        yield 'item without price' => [['items' => [['name' => 'A', 'qty' => 1]]], RejectionCode::InvalidItem, 'items[0].price'];
        yield 'bad total' => [['total' => 'много'], RejectionCode::InvalidPayload, 'total'];
        yield 'no delivery type' => [['delivery' => ['type' => null]], RejectionCode::MissingField, 'delivery.type'];
        yield 'unknown delivery type' => [['delivery' => ['type' => 'drone']], RejectionCode::InvalidDeliveryType, 'delivery.type'];
        yield 'no district' => [['delivery' => ['district' => null]], RejectionCode::MissingField, 'delivery.district'];
        yield 'empty address' => [['delivery' => ['address' => '']], RejectionCode::MissingField, 'delivery.address'];
        yield 'no date' => [['delivery' => ['date' => null]], RejectionCode::MissingField, 'delivery.date'];
        yield 'no time_from' => [['delivery' => ['time_from' => '']], RejectionCode::MissingField, 'delivery.time_from'];
        yield 'no time_to' => [['delivery' => ['time_to' => null]], RejectionCode::MissingField, 'delivery.time_to'];
        yield 'interval end before start' => [['delivery' => ['time_from' => '16:00', 'time_to' => '14:00']], RejectionCode::InvalidInterval, 'delivery.time_to'];
        yield 'empty interval' => [['delivery' => ['time_from' => '16:00', 'time_to' => '16:00']], RejectionCode::InvalidInterval, 'delivery.time_to'];
        yield 'garbage time' => [['delivery' => ['time_from' => 'утром']], RejectionCode::InvalidDatetime, 'delivery.time_from'];
        yield 'hour 24' => [['delivery' => ['time_to' => '24:00']], RejectionCode::InvalidDatetime, 'delivery.time_to'];
        yield 'garbage slot date' => [['delivery' => ['date' => '10.10.2026']], RejectionCode::InvalidDatetime, 'delivery.date'];
        yield 'impossible slot date' => [['delivery' => ['date' => '2026-02-30']], RejectionCode::InvalidDatetime, 'delivery.date'];
        yield 'created_at is not a string' => [['created_at' => 1760000000], RejectionCode::InvalidDatetime, 'created_at'];
        yield 'created_at in words' => [['created_at' => 'вчера'], RejectionCode::InvalidDatetime, 'created_at'];
        yield 'created_at month 13' => [['created_at' => '2026-13-01T10:00:00+03:00'], RejectionCode::InvalidDatetime, 'created_at'];
        yield 'item is not an object' => [['items' => ['Букет']], RejectionCode::InvalidItem, 'items[0]'];
        yield 'impossible date' => [['created_at' => '2026-02-30T10:00:00'], RejectionCode::InvalidDatetime, 'created_at'];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('rejections')]
    public function testRejection(array $overrides, RejectionCode $code, string $field): void
    {
        try {
            $this->parser->parse(OrderFactory::raw($overrides));
            $this->fail('Expected rejection ' . $code->value);
        } catch (OrderRejected $e) {
            $this->assertSame($code, $e->reason);
            $this->assertSame($field, $e->field);
            $this->assertNotSame('', $e->getMessage());
        }
    }

    public function testNonObjectRecordIsRejected(): void
    {
        $this->expectException(OrderRejected::class);
        $this->parser->parse('MP-1');
    }
}
