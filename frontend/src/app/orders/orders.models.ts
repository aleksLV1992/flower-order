export type OrderStatus = 'new' | 'accepted' | 'delivering' | 'delivered' | 'cancelled';
export type ImportOutcome = 'created' | 'updated' | 'unchanged' | 'duplicate' | 'rejected';

export interface Note {
  code: string;
  message: string;
}

export interface ImportResult {
  index: number;
  externalId: string | null;
  outcome: ImportOutcome;
  orderId: number | null;
  status: OrderStatus | null;
  previousStatus: OrderStatus | null;
  reason: (Note & { field: string | null }) | null;
  notes: Note[];
}

export interface ImportReport {
  summary: Record<ImportOutcome | 'total', number>;
  results: ImportResult[];
}

export interface DeliverySlot {
  from: string;
  to: string;
}

export interface OrderItem {
  sku: string | null;
  name: string;
  qty: number;
  unitPrice: number;
  lineTotal: number;
}

export interface DeliveryCharge {
  code: string;
  label: string;
  amount: number;
}

export interface Order {
  id: number;
  externalId: string;
  status: OrderStatus;
  customer: { name: string; phone: string };
  deliveryType: 'delivery' | 'pickup';
  district: string | null;
  address: string | null;
  interval: DeliverySlot | null;
  placedAt: string | null;
  items: OrderItem[];
  itemsTotal: number;
  declaredTotal: number | null;
  needsReview: boolean;
  reviewReasons: Note[];
  delivery: { cost: number; breakdown: DeliveryCharge[] };
  grandTotal: number;
  updatedAt: string;
}

export interface OrderPage {
  items: Order[];
  page: number;
  perPage: number;
  total: number;
  statusCounts: Record<OrderStatus, number>;
  shop: { id: number; name: string; timezone: string };
}
