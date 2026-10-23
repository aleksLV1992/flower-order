import { HttpErrorResponse } from '@angular/common/http';
import { ImportOutcome, ImportResult, OrderStatus } from './orders.models';

export const STATUSES: readonly OrderStatus[] = [
  'new',
  'accepted',
  'delivering',
  'delivered',
  'cancelled',
];
export const OUTCOMES: readonly ImportOutcome[] = [
  'created',
  'updated',
  'unchanged',
  'duplicate',
  'rejected',
];

export const STATUS_LABELS: Record<OrderStatus, string> = {
  new: 'Новый',
  accepted: 'Принят',
  delivering: 'В доставке',
  delivered: 'Доставлен',
  cancelled: 'Отменён',
};

export const OUTCOME_LABELS: Record<ImportOutcome, string> = {
  created: 'Создан',
  updated: 'Обновлён',
  unchanged: 'Без изменений',
  duplicate: 'Дубль',
  rejected: 'Отклонён',
};

const DISTRICT_LABELS: Partial<Record<string, string>> = {
  centre: 'Центр',
  north: 'Север',
  south: 'Юг',
  suburb: 'Пригород',
};

export function districtLabel(code: string | null): string {
  return code === null ? '' : (DISTRICT_LABELS[code] ?? code);
}

export function isProblem(result: ImportResult): boolean {
  return result.outcome === 'rejected' || result.outcome === 'duplicate' || result.notes.length > 0;
}

export function errorMessage(err: unknown): string {
  if (err instanceof HttpErrorResponse) {
    if (err.status === 0) {
      return 'Нет связи с сервером. Проверьте интернет и попробуйте ещё раз.';
    }
    const body = err.error as { error?: { message?: string } } | null;
    return body?.error?.message ?? `Ошибка сервера (${err.status}). Попробуйте ещё раз.`;
  }
  return 'Что-то пошло не так. Попробуйте ещё раз.';
}
