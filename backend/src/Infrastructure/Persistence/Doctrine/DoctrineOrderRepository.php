<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\OrderStatus;
use App\Domain\Shop\Shop;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final readonly class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function add(Order $order): void
    {
        $this->em->persist($order);
    }

    #[\Override]
    public function findByExternalIds(Shop $shop, array $externalIds): array
    {
        if ($externalIds === []) {
            return [];
        }
        /** @var list<Order> $orders */
        $orders = $this->forShop($shop)
            ->andWhere('o.externalId IN (:ids)')
            ->setParameter('ids', $externalIds)
            ->getQuery()
            ->getResult();

        return array_column(array_map(static fn (Order $o): array => [$o->externalId, $o], $orders), 1, 0);
    }

    #[\Override]
    public function page(Shop $shop, ?OrderStatus $status, int $page, int $perPage): array
    {
        $qb = $this->forShop($shop);
        if ($status instanceof OrderStatus) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        $total = (int) (clone $qb)->select('COUNT(o.id)')->getQuery()->getSingleScalarResult();

        /** @var list<int> $ids */
        $ids = (clone $qb)->select('o.id')
            ->orderBy('o.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getSingleColumnResult();

        if ($ids === []) {
            return ['items' => [], 'total' => $total];
        }

        /** @var list<Order> $items */
        $items = $this->em->createQueryBuilder()
            ->select('o', 'i')
            ->from(Order::class, 'o')
            ->leftJoin('o.itemCollection', 'i')
            ->andWhere('o.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('o.id', 'DESC')
            ->addOrderBy('i.position', 'ASC')
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    #[\Override]
    public function countByStatus(Shop $shop): array
    {
        /** @var list<array{status: OrderStatus, cnt: int|string}> $rows */
        $rows = $this->forShop($shop)
            ->select('o.status AS status, COUNT(o.id) AS cnt')
            ->groupBy('o.status')
            ->getQuery()
            ->getArrayResult();

        $counts = array_fill_keys(array_map(static fn (OrderStatus $s): string => $s->value, OrderStatus::cases()), 0);
        foreach ($rows as $row) {
            $counts[$row['status']->value] = (int) $row['cnt'];
        }

        return $counts;
    }

    private function forShop(Shop $shop): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->andWhere('o.shop = :shop')
            ->setParameter('shop', $shop);
    }
}
