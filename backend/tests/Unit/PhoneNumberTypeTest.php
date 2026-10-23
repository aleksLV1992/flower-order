<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Order\PhoneNumber;
use App\Infrastructure\Persistence\Doctrine\PhoneNumberType;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTypeTest extends TestCase
{
    private PhoneNumberType $type;
    private MySQL84Platform $platform;

    protected function setUp(): void
    {
        $this->type = new PhoneNumberType();
        $this->platform = new MySQL84Platform();
    }

    public function testRoundTrip(): void
    {
        $phone = PhoneNumber::tryFromRaw('8 900 444 33 22');

        $this->assertSame('+79004443322', $this->type->convertToDatabaseValue($phone, $this->platform));
        $this->assertEquals($phone, $this->type->convertToPHPValue('+79004443322', $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
        $this->assertSame('VARCHAR(12)', $this->type->getSQLDeclaration([], $this->platform));
    }

    public function testNonStringFromDatabaseFailsLoudly(): void
    {
        $this->expectException(ValueNotConvertible::class);
        $this->type->convertToPHPValue(79001234567, $this->platform);
    }

    public function testCorruptedValueInDatabaseFailsLoudly(): void
    {
        $this->expectException(ValueNotConvertible::class);
        $this->type->convertToPHPValue('12-34', $this->platform);
    }
}
