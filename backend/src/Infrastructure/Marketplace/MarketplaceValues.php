<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace;

use App\Application\Import\MarketplaceStatus;
use App\Domain\Order\DeliveryType;
use App\Domain\Order\OrderStatus;
use App\Domain\Order\PhoneNumber;

final class MarketplaceValues
{
    private const string TIMEZONE = 'Europe/Moscow';

    public static function text(string $value): string
    {
        return trim($value);
    }

    public static function optionalText(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }

    public static function isMoney(mixed $value): bool
    {
        return (is_int($value) || is_float($value) || is_string($value))
            && preg_match('/^\d+(\.\d{1,2})?$/', trim((string) $value)) === 1;
    }

    public static function kopecks(int|float|string $value): int
    {
        if (preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', trim((string) $value), $m) !== 1) {
            throw new \LogicException('Money must be validated before mapping');
        }

        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    public static function nullableKopecks(int|float|string|null $value): ?int
    {
        return $value === null ? null : self::kopecks($value);
    }

    public static function dateTime(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2})?/', $value) !== 1) {
            return null;
        }
        $hasOffset = preg_match('/(Z|[+-]\d{2}:?\d{2})$/', $value) === 1;
        try {
            $dt = new \DateTimeImmutable($value, $hasOffset ? null : new \DateTimeZone(self::TIMEZONE));
        } catch (\Exception) {
            return null;
        }
        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors !== false && $errors['warning_count'] > 0) {
            return null;
        }

        return $dt->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function isDate(string $date): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && $dt !== false && $dt->format('Y-m-d') === $date;
    }

    public static function isTime(string $time): bool
    {
        return preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    public static function slot(string $date, string $time): \DateTimeImmutable
    {
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d G:i', $date . ' ' . $time, new \DateTimeZone(self::TIMEZONE));
        if ($dt === false) {
            throw new \LogicException('Slot must be validated before mapping');
        }

        return $dt->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function status(string $raw): OrderStatus
    {
        return MarketplaceStatus::toDomain($raw) ?? throw new \LogicException('Status must be validated before mapping');
    }

    public static function phone(string $raw): PhoneNumber
    {
        return PhoneNumber::tryFromRaw($raw) ?? throw new \LogicException('Phone must be validated before mapping');
    }

    public static function deliveryType(string $raw): ?DeliveryType
    {
        return DeliveryType::tryFrom(strtolower(trim($raw)));
    }

    public static function rub(int $kopecks): string
    {
        return $kopecks % 100 === 0
            ? number_format(intdiv($kopecks, 100), 0, ',', ' ')
            : number_format($kopecks / 100, 2, ',', ' ');
    }
}
