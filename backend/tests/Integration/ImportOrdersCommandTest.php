<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportOrdersCommandTest extends DatabaseTestCase
{
    private CommandTester $tester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        self::resetOrders();
        $this->tester = new CommandTester(new Application($kernel)->find('app:orders:import'));
    }

    public function testImportsFileAndPrintsReport(): void
    {
        $this->assertSame(Command::SUCCESS, $this->tester->execute(['file' => self::FIXTURE, 'shop' => '1']));

        /** @var array{summary: array<string, int>} $report */
        $report = json_decode($this->tester->getDisplay(), true, 64, \JSON_THROW_ON_ERROR);
        $this->assertSame(7, $report['summary']['created']);
        $this->assertStringContainsString('Для района «zarechye» нет тарифа доставки', $this->tester->getDisplay());
    }

    public function testFileWithoutOrdersIsRefused(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'orders');
        file_put_contents($file, '{"foo": 1}');

        $this->assertSame(Command::FAILURE, $this->tester->execute(['file' => $file]));
        $this->assertStringContainsString('orders', $this->tester->getDisplay());
        unlink($file);
    }

    public function testMissingFile(): void
    {
        $this->assertSame(Command::FAILURE, $this->tester->execute(['file' => '/nope.json']));
        $this->assertStringContainsString('Cannot read /nope.json', $this->tester->getDisplay());
    }
}
