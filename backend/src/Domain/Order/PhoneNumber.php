<?php

declare(strict_types=1);

namespace App\Domain\Order;

final readonly class PhoneNumber
{
    private function __construct(public string $value)
    {
    }

    #[\NoDiscard]
    public static function tryFromRaw(string $raw): ?self
    {
        $raw = trim($raw);
        if ($raw === '' || preg_match('/^\+?[\d\s\-()]+$/', $raw) !== 1) {
            return null;
        }

        $digits = $raw |> (static fn (string $s): string => (string) preg_replace('/\D/', '', $s));

        return match (true) {
            strlen($digits) !== 11 => null,
            $digits[0] === '8' && !str_starts_with($raw, '+') => new self('+7' . substr($digits, 1)),
            $digits[0] === '7' => new self('+' . $digits),
            default => null,
        };
    }
}
