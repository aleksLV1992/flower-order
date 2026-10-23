<?php

declare(strict_types=1);

namespace App\Domain\Order;

enum DeliveryType: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';
}
