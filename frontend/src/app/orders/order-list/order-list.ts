import { ChangeDetectionStrategy, Component, computed, inject } from '@angular/core';
import { STATUS_LABELS, STATUSES } from '../order-labels';
import { Order } from '../orders.models';
import { OrdersStore } from '../orders-store';
import { DeliverySlotPipe } from '../pipes/delivery-slot-pipe';
import { DistrictPipe } from '../pipes/district-pipe';
import { MoneyPipe } from '../pipes/money-pipe';
import { PhonePipe } from '../pipes/phone-pipe';

interface OrderRow {
  order: Order;
  urgent: boolean;
  reviewText: string;
  declaredMismatch: number | null;
}

@Component({
  selector: 'app-order-list',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [MoneyPipe, PhonePipe, DistrictPipe, DeliverySlotPipe],
  templateUrl: './order-list.html',
  styleUrl: './order-list.scss',
})
export class OrderList {
  protected readonly store = inject(OrdersStore);
  protected readonly statuses = STATUSES;
  protected readonly statusLabels = STATUS_LABELS;

  protected readonly rows = computed<OrderRow[]>(() =>
    (this.store.page()?.items ?? []).map((order) => ({
      order,
      urgent: order.delivery.breakdown.some((charge) => charge.code === 'urgency'),
      reviewText: order.reviewReasons.map((reason) => reason.message).join('; '),
      declaredMismatch:
        order.declaredTotal !== null && order.declaredTotal !== order.itemsTotal
          ? order.declaredTotal
          : null,
    })),
  );
}
