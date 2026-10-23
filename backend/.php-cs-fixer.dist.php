<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/migrations', __DIR__ . '/config'])
    ->notPath('reference.php')
    ->append([__FILE__, __DIR__ . '/rector.php']);

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP8x5Migration' => true,
        '@PHP8x5Migration:risky' => true,
        'declare_strict_types' => true,
        'yoda_style' => false,
        'concat_space' => ['spacing' => 'one'],
        'phpdoc_to_comment' => false,
        'global_namespace_import' => ['import_classes' => false],
        'native_function_invocation' => false,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache');
