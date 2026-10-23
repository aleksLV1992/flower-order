<?php

declare(strict_types=1);

namespace App\Domain\Shop;

interface ShopRepository
{
    /**
     * @throws ShopNotFound
     */
    public function get(int $id): Shop;

    /**
     * @throws ShopNotFound
     */
    public function getForUpdate(int $id): Shop;
}
