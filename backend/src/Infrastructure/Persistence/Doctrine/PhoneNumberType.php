<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Order\PhoneNumber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

final class PhoneNumberType extends Type
{
    public const string NAME = 'phone_number';

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 12] + $column);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PhoneNumber
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw ValueNotConvertible::new($value, self::NAME);
        }

        return PhoneNumber::tryFromRaw($value) ?? throw ValueNotConvertible::new($value, self::NAME);
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof PhoneNumber ? $value->value : null;
    }
}
