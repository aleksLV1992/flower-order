<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class UnknownShop extends \RuntimeException
{
    public static function withId(int $id, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Shop %d not found', $id), 0, $previous);
    }
}
