import { httpResource } from '@angular/common/http';
import { Injectable, computed, linkedSignal, signal } from '@angular/core';
import { errorMessage } from './order-labels';
import { OrderPage, OrderStatus } from './orders.models';
import { ordersUrl } from './orders-api';

export const PER_PAGE = 20;

@Injectable({ providedIn: 'root' })
export class OrdersStore {
  readonly status = signal<OrderStatus | null>(null);
  readonly pageNo = signal(1);

  private readonly resource = httpResource<OrderPage>(() =>
    ordersUrl(this.status(), this.pageNo(), PER_PAGE),
  );

  readonly page = linkedSignal<OrderPage | undefined, OrderPage | undefined>({
    source: () => (this.resource.hasValue() ? this.resource.value() : undefined),
    computation: (next, previous) => next ?? previous?.value,
  });
  readonly loading = computed(() => this.resource.isLoading());
  readonly error = computed(() => {
    const err = this.resource.error();
    return err ? errorMessage(err) : null;
  });
  readonly counts = computed(() => this.page()?.statusCounts);
  readonly totalCount = computed(() =>
    Object.values(this.counts() ?? {}).reduce((sum, n) => sum + n, 0),
  );
  readonly pages = computed(() => {
    const page = this.page();
    return page ? Math.max(1, Math.ceil(page.total / page.perPage)) : 1;
  });
  readonly shopName = computed(() => this.page()?.shop.name ?? null);

  filterBy(status: OrderStatus | null): void {
    this.status.set(status);
    this.pageNo.set(1);
  }

  goToPage(pageNo: number): void {
    this.pageNo.set(pageNo);
  }

  reload(): void {
    this.resource.reload();
  }
}
