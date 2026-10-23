<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Infrastructure\Marketplace\MarketplaceValues;
use PHPUnit\Framework\TestCase;

final class MarketplaceValuesTest extends TestCase
{
    public function testKopecks(): void
    {
        $this->assertSame([199050, 300000, 10], [MarketplaceValues::kopecks(1990.5), MarketplaceValues::kopecks('3000'), MarketplaceValues::kopecks('0.10')]);
    }

    public function testMappingUnvalidatedMoneyFailsLoudly(): void
    {
        $this->expectException(\LogicException::class);
        MarketplaceValues::kopecks('много');
    }

    public function testMappingUnvalidatedSlotFailsLoudly(): void
    {
        $this->expectException(\LogicException::class);
        MarketplaceValues::slot('2026-10-10', 'утром');
    }
}
