<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Delivery\DeliveryTariff;
use App\Domain\Delivery\DistrictTariff;
use App\Domain\Delivery\TariffBook;
use App\Domain\Delivery\TariffRepository;
use App\Domain\Shop\Shop;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTariffRepository implements TariffRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function bookFor(Shop $shop): TariffBook
    {
        $tariffs = $this->em->getRepository(DeliveryTariff::class)->findBy(['shop' => $shop]);

        return new TariffBook(array_map(static fn (DeliveryTariff $t): DistrictTariff => $t->toDistrictTariff(), $tariffs));
    }
}
