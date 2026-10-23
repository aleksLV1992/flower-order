<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Order\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function phones(): iterable
    {
        yield 'already normalized' => ['+79123456789', '+79123456789'];
        yield 'leading 8 with brackets' => ['8 (912) 345-67-89', '+79123456789'];
        yield 'leading 8 with dashes' => ['8-902-111-00-99', '+79021110099'];
        yield 'plain 8' => ['89001112233', '+79001112233'];
        yield 'spaces with 8' => ['8 900 444 33 22', '+79004443322'];
        yield '+7 with brackets' => ['+7 (900) 123-45-67', '+79001234567'];
        yield '7 without plus' => ['79123456789', '+79123456789'];
        yield 'surrounding whitespace' => ['  +7 (900) 111-22-33 ', '+79001112233'];
        yield 'too short (fixture MP-1007)' => ['123', null];
        yield '10 digits without country code' => ['9123456789', null];
        yield '12 digits' => ['+791234567890', null];
        yield '+8 is not a valid notation' => ['+89123456789', null];
        yield 'foreign code' => ['+1 912 345 67 89', null];
        yield 'letters' => ['+7 912 ABC 67 89', null];
        yield 'extension text' => ['+79123456789 доб. 12', null];
        yield 'empty' => ['', null];
    }

    #[DataProvider('phones')]
    public function testTryFromRaw(string $raw, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::tryFromRaw($raw)?->value);
    }
}
