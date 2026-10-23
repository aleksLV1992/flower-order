<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Component\Process\Process;

final class ConcurrentImportTest extends DatabaseTestCase
{
    public function testParallelImportsCreateEachOrderOnce(): void
    {
        self::bootKernel();
        self::resetOrders();

        $processes = [];
        for ($i = 0; $i < 6; ++$i) {
            $p = new Process(['php', 'bin/console', 'app:orders:import', self::FIXTURE, '1', '--env=test'], dirname(__DIR__, 2));
            $p->start();
            $processes[] = $p;
        }

        $created = 0;
        $unchanged = 0;
        foreach ($processes as $p) {
            $p->wait();
            $this->assertTrue($p->isSuccessful(), $p->getErrorOutput() . $p->getOutput());
            /** @var array{summary: array<string, int>} $report */
            $report = json_decode($p->getOutput(), true, 64, \JSON_THROW_ON_ERROR);
            $summary = $report['summary'];
            $created += $summary['created'];
            $unchanged += $summary['unchanged'];
            $this->assertSame(3, $summary['rejected']);
            $this->assertSame(1, $summary['duplicate']);
        }

        $this->assertSame(7, $created);
        $this->assertSame(35, $unchanged);
        $this->assertSame(7, (int) self::db()->fetchOne('SELECT COUNT(*) FROM orders'));
        $this->assertSame(7, (int) self::db()->fetchOne('SELECT COUNT(*) FROM order_item'));
    }
}
