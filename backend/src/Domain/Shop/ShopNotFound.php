<?php

declare(strict_types=1);

namespace App\Domain\Shop;

final class ShopNotFound extends \DomainException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Shop %d not found', $id));
    }
}
