<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseTestCase extends WebTestCase
{
    protected const FIXTURE = __DIR__ . '/../Fixtures/marketplace-orders.json';

    protected static function resetOrders(): void
    {
        /** @var Connection $db */
        $db = static::getContainer()->get(Connection::class);
        $db->executeStatement('DELETE FROM order_item');
        $db->executeStatement('DELETE FROM orders');
    }

    /**
     * @return list<mixed>
     */
    protected static function fixture(): array
    {
        $file = json_decode((string) file_get_contents(self::FIXTURE), true, 64, \JSON_THROW_ON_ERROR);
        \assert(\is_array($file) && \is_array($file['orders']) && array_is_list($file['orders']));

        return $file['orders'];
    }

    protected static function db(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }
}
