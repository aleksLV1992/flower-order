import { DeliverySlot } from './orders.models';

export const SHOP_TZ = 'Europe/Moscow';

const rubWhole = new Intl.NumberFormat('ru-RU', {
  style: 'currency',
  currency: 'RUB',
  maximumFractionDigits: 0,
});
const rubKopecks = new Intl.NumberFormat('ru-RU', {
  style: 'currency',
  currency: 'RUB',
  minimumFractionDigits: 2,
});
const dayTime = new Intl.DateTimeFormat('ru-RU', {
  timeZone: SHOP_TZ,
  day: 'numeric',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit',
});
const time = new Intl.DateTimeFormat('ru-RU', {
  timeZone: SHOP_TZ,
  hour: '2-digit',
  minute: '2-digit',
});

export function money(kopecks: number): string {
  const format = kopecks % 100 === 0 ? rubWhole : rubKopecks;
  return format.format(kopecks / 100).replace(/\u00a0|\u202f/g, ' ');
}

export function deliverySlot(slot: DeliverySlot): string {
  return `${dayTime.format(new Date(slot.from))}–${time.format(new Date(slot.to))}`;
}

export function phone(value: string): string {
  const m = /^\+7(\d{3})(\d{3})(\d{2})(\d{2})$/.exec(value);
  return m ? `+7 ${m[1]} ${m[2]}-${m[3]}-${m[4]}` : value;
}
