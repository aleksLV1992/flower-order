<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Port\TransactionManager;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactionManager implements TransactionManager
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function transactional(callable $work): mixed
    {
        return $this->em->wrapInTransaction(static fn (): mixed => $work());
    }
}
