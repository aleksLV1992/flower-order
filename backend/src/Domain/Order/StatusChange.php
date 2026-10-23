<?php

declare(strict_types=1);

namespace App\Domain\Order;

enum StatusChange
{
    case Changed;
    case Same;
    case FinalKept;
}
