<?php

declare(strict_types=1);

namespace App\Application\Order\View;

use App\Application\View\NoteView;
use App\Domain\Delivery\DeliveryCharge;
use App\Domain\Delivery\DeliveryQuote;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\ReviewReason;

final class OrderViewTransform
{
    public static function enumValue(\BackedEnum $value): string|int
    {
        return $value->value;
    }

    public static function customer(string $name, Order $order): CustomerView
    {
        return new CustomerView($name, $order->customerPhone->value);
    }

    public static function interval(?\DateTimeImmutable $from, Order $order): ?IntervalView
    {
        return $from instanceof \DateTimeImmutable && $order->intervalEnd instanceof \DateTimeImmutable ? new IntervalView($from, $order->intervalEnd) : null;
    }

    /**
     * @param list<OrderItem> $items
     *
     * @return list<OrderItemView>
     */
    public static function items(array $items): array
    {
        return array_map(static fn (OrderItem $i): OrderItemView => new OrderItemView($i->sku, $i->name, $i->qty, $i->unitPrice, $i->lineTotal), $items);
    }

    /**
     * @param list<ReviewReason> $reasons
     *
     * @return list<NoteView>
     */
    public static function notes(array $reasons): array
    {
        return array_map(static fn (ReviewReason $r): NoteView => new NoteView($r->code, $r->message), $reasons);
    }

    public static function delivery(DeliveryQuote $quote): DeliveryView
    {
        return new DeliveryView($quote->total(), array_map(
            static fn (DeliveryCharge $c): DeliveryChargeView => new DeliveryChargeView($c->code, $c->label, $c->amount),
            $quote->charges,
        ));
    }
}
