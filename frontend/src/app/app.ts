import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { ImportPanel } from './orders/import-panel/import-panel';
import { OrderList } from './orders/order-list/order-list';
import { OrdersStore } from './orders/orders-store';

@Component({
  selector: 'app-root',
  imports: [ImportPanel, OrderList],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './app.html',
  styleUrl: './app.scss',
})
export class App {
  protected readonly orders = inject(OrdersStore);
}
