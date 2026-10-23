<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Delivery\DeliveryRequest;
use App\Domain\Delivery\DeliveryTariff;
use App\Domain\Delivery\DistrictTariff;
use App\Domain\Order\DeliveryType;
use App\Domain\Order\OrderLine;
use App\Domain\Shop\Shop;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DomainInvariantsTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int}>
     */
    public static function badLines(): iterable
    {
        yield 'zero qty' => [0, 100];
        yield 'negative qty' => [-1, 100];
        yield 'negative price' => [1, -1];
    }

    #[DataProvider('badLines')]
    public function testOrderLineRejectsImpossibleValues(int $qty, int $price): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrderLine('Букет', $qty, $price);
    }

    public function testOrderLineTotal(): void
    {
        $this->assertSame(360000, new OrderLine('Букет', 2, 180000)->total());
        $this->assertSame(0, new OrderLine('Упаковка', 1, 0)->total());
    }

    public function testDeliveryRequestNeedsDistrictAndInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DeliveryRequest(DeliveryType::Delivery, 1000, 'centre');
    }

    public function testPickupRequestNeedsNothingElse(): void
    {
        $this->assertNull(new DeliveryRequest(DeliveryType::Pickup, 1000)->district);
    }

    public function testTariffEntityToValueObject(): void
    {
        $tariff = new DeliveryTariff(new Shop('Лавка'), 'suburb', 'Пригород', 70000, null);

        $this->assertEquals(new DistrictTariff('suburb', 'Пригород', 70000, null), $tariff->toDistrictTariff());
    }
}
