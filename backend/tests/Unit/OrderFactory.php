<?php

declare(strict_types=1);

namespace App\Tests\Unit;

final class OrderFactory
{
    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<array-key, mixed>
     */
    public static function raw(array $overrides = []): array
    {
        $base = [
            'id' => 'MP-1',
            'status' => 'NEW',
            'created_at' => '2026-10-20T09:00:00+03:00',
            'customer' => ['name' => 'Анна', 'phone' => '+79123456789'],
            'delivery' => [
                'type' => 'delivery',
                'district' => 'centre',
                'address' => 'ул. Ленина, 1',
                'date' => '2026-10-20',
                'time_from' => '14:00',
                'time_to' => '16:00',
            ],
            'items' => [['sku' => 'B-1', 'name' => 'Букет', 'qty' => 1, 'price' => 1000]],
            'total' => 1000,
        ];

        return self::merge($base, $overrides);
    }

    /**
     * @param array<array-key, mixed> $base
     * @param array<array-key, mixed> $overrides
     *
     * @return array<array-key, mixed>
     */
    private static function merge(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($base[$key]);
            } elseif (is_array($value) && !array_is_list($value) && is_array($base[$key] ?? null)) {
                $base[$key] = self::merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
