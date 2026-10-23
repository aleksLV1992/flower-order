import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, switchMap } from 'rxjs';
import { ImportReport, OrderStatus } from './orders.models';

export const SHOP_ID = 1;
export const FIXTURE_URL = 'marketplace-orders.json';

export function ordersUrl(status: OrderStatus | null, page: number, perPage: number): string {
  const params = new URLSearchParams({ page: String(page), perPage: String(perPage) });
  if (status) {
    params.set('status', status);
  }
  return `/api/shops/${SHOP_ID}/orders?${params}`;
}

@Injectable({ providedIn: 'root' })
export class OrdersApi {
  private readonly http = inject(HttpClient);

  importFixture(): Observable<ImportReport> {
    return this.http.get(FIXTURE_URL, { responseType: 'text' }).pipe(
      switchMap((body) =>
        this.http.post<ImportReport>(`/api/shops/${SHOP_ID}/imports`, body, {
          headers: { 'Content-Type': 'application/json' },
        }),
      ),
    );
  }
}
