import { ImportReport, ImportResult, Order, OrderPage } from '../orders.models';

export function result(partial: Partial<ImportResult>): ImportResult {
  return {
    index: 0,
    externalId: 'MP-1',
    outcome: 'created',
    orderId: 1,
    status: 'new',
    previousStatus: null,
    reason: null,
    notes: [],
    ...partial,
  };
}

export function report(results: ImportResult[]): ImportReport {
  const summary = {
    total: results.length,
    created: 0,
    updated: 0,
    unchanged: 0,
    duplicate: 0,
    rejected: 0,
  };
  results.forEach((r) => summary[r.outcome]++);
  return { summary, results };
}

export function order(partial: Partial<Order>): Order {
  return {
    id: 1,
    externalId: 'MP-1',
    status: 'new',
    customer: { name: 'Анна', phone: '+79123456789' },
    deliveryType: 'delivery',
    district: 'centre',
    address: 'ул. Ленина, 1',
    interval: { from: '2026-10-20T11:00:00+00:00', to: '2026-10-20T13:00:00+00:00' },
    placedAt: '2026-10-20T06:00:00+00:00',
    items: [{ sku: 'B-1', name: 'Букет', qty: 1, unitPrice: 280000, lineTotal: 280000 }],
    itemsTotal: 280000,
    declaredTotal: 280000,
    needsReview: false,
    reviewReasons: [],
    delivery: {
      cost: 30000,
      breakdown: [{ code: 'base', amount: 30000, label: 'Доставка, район «Центр»' }],
    },
    grandTotal: 310000,
    updatedAt: '2026-10-22T10:00:00+00:00',
    ...partial,
  };
}

export function page(items: Order[], extra: Partial<OrderPage> = {}): OrderPage {
  return {
    items,
    page: 1,
    perPage: 20,
    total: items.length,
    statusCounts: { new: items.length, accepted: 0, delivering: 0, delivered: 0, cancelled: 0 },
    shop: { id: 1, name: 'Лавка', timezone: 'Europe/Moscow' },
    ...extra,
  };
}
