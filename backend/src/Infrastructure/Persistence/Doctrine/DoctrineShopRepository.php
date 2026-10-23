<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Shop\Shop;
use App\Domain\Shop\ShopNotFound;
use App\Domain\Shop\ShopRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineShopRepository implements ShopRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function get(int $id): Shop
    {
        return $this->em->find(Shop::class, $id) ?? throw ShopNotFound::withId($id);
    }

    #[\Override]
    public function getForUpdate(int $id): Shop
    {
        return $this->em->find(Shop::class, $id, LockMode::PESSIMISTIC_WRITE) ?? throw ShopNotFound::withId($id);
    }
}
