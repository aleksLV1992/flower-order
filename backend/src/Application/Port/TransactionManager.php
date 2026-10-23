<?php

declare(strict_types=1);

namespace App\Application\Port;

interface TransactionManager
{
    /**
     * @template T
     *
     * @param callable(): T $work
     *
     * @return T
     */
    public function transactional(callable $work): mixed;
}
