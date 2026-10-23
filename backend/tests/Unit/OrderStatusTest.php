<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Import\MarketplaceStatus;
use App\Domain\Order\OrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    /**
     * @return iterable<array{string, ?OrderStatus}>
     */
    public static function statuses(): iterable
    {
        yield ['NEW', OrderStatus::New];
        yield ['ACCEPTED', OrderStatus::Accepted];
        yield ['IN_DELIVERY', OrderStatus::Delivering];
        yield ['DONE', OrderStatus::Delivered];
        yield ['CANCELED', OrderStatus::Cancelled];
        yield [' done ', OrderStatus::Delivered];
        yield ['CANCELLED', null];
        yield ['PAID', null];
        yield ['delivered', null];
        yield ['PACKING', null];
        yield ['', null];
    }

    #[DataProvider('statuses')]
    public function testMarketplaceMapping(string $raw, ?OrderStatus $expected): void
    {
        $this->assertSame($expected, MarketplaceStatus::toDomain($raw));
    }

    public function testFinalStatuses(): void
    {
        $final = array_values(array_filter(OrderStatus::cases(), static fn (OrderStatus $s): bool => $s->isFinal()));
        $this->assertSame([OrderStatus::Delivered, OrderStatus::Cancelled], $final);
    }
}
