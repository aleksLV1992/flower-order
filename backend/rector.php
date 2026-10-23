<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/migrations'])
    ->withPhpSets()
    ->withComposerBased(doctrine: true, phpunit: true, symfony: true)
    ->withAttributesSets(symfony: true, doctrine: true, phpunit: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
        symfonyCodeQuality: true,
        doctrineCodeQuality: true,
        phpunitCodeQuality: true,
    )
    ->withSkip([
        ReadOnlyPropertyRector::class => [__DIR__ . '/src/Domain/*/*.php'],
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withCache(__DIR__ . '/var/rector');
