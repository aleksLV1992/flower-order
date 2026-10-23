<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

new Dotenv()->bootEnv(__DIR__ . '/../.env');

if (filter_var($_SERVER['APP_DEBUG'] ?? false, \FILTER_VALIDATE_BOOL)) {
    umask(0o000);
}

if (getenv('SKIP_TEST_MIGRATIONS') !== '1') {
    passthru(sprintf('php %s/bin/console doctrine:migrations:migrate --no-interaction --env=test --quiet', escapeshellarg(dirname(__DIR__))), $code);
    if ($code !== 0) {
        exit($code);
    }
}
