<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Application\Import\ImportOrders;
use App\Application\Import\ImportOrdersCommand;
use App\UI\Request\ImportRequest;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand('app:orders:import', 'Import a marketplace JSON file for a shop')]
final readonly class ImportOrdersCliCommand
{
    public function __construct(
        private ImportOrders $importOrders,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Path to the marketplace JSON file')]
        string $file,
        #[Argument('Shop id')]
        int $shop = 1,
    ): int {
        $json = @file_get_contents($file);
        if ($json === false) {
            $io->error(sprintf('Cannot read %s', $file));

            return Command::FAILURE;
        }
        $request = $this->serializer->deserialize($json, ImportRequest::class, 'json');
        $violations = $this->validator->validate($request);
        if (count($violations) > 0) {
            $io->error((string) $violations);

            return Command::FAILURE;
        }

        $report = ($this->importOrders)(new ImportOrdersCommand($shop, $request->orders ?? []));
        $io->writeln($this->serializer->serialize($report, 'json', [
            JsonEncode::OPTIONS => \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR,
        ]));

        return Command::SUCCESS;
    }
}
