import { HttpErrorResponse } from '@angular/common/http';
import { deliverySlot, money, phone } from './formatting';
import { districtLabel, errorMessage } from './order-labels';
import { ordersUrl } from './orders-api';
import { DeliverySlotPipe } from './pipes/delivery-slot-pipe';
import { DistrictPipe } from './pipes/district-pipe';
import { MoneyPipe } from './pipes/money-pipe';
import { PhonePipe } from './pipes/phone-pipe';

describe('pipes', () => {
  it('delegate to the formatting functions', () => {
    expect(new MoneyPipe().transform(560000)).toBe('5 600 ₽');
    expect(new PhonePipe().transform('+79001112233')).toBe('+7 900 111-22-33');
    expect(new DistrictPipe().transform('suburb')).toBe('Пригород');
    expect(
      new DeliverySlotPipe().transform({
        from: '2026-10-10T09:00:00+00:00',
        to: '2026-10-10T11:00:00+00:00',
      }),
    ).toBe('10 окт., 12:00–14:00');
  });
});

describe('formatting', () => {
  it('formats kopecks as rubles', () => {
    expect(money(310000)).toBe('3 100 ₽');
    expect(money(428150)).toBe('4 281,50 ₽');
    expect(money(0)).toBe('0 ₽');
  });

  it('shows intervals in shop time (Moscow) whatever the device timezone', () => {
    expect(
      deliverySlot({ from: '2026-10-20T11:00:00+00:00', to: '2026-10-20T13:00:00+00:00' }),
    ).toBe('20 окт., 14:00–16:00');
  });

  it('pretty-prints normalized phones', () => {
    expect(phone('+79123456789')).toBe('+7 912 345-67-89');
    expect(phone('weird')).toBe('weird');
  });

  it('turns API and network errors into messages', () => {
    expect(errorMessage(new HttpErrorResponse({ status: 0 }))).toContain('Нет связи');
    expect(
      errorMessage(
        new HttpErrorResponse({
          status: 404,
          error: { error: { code: 'shop_not_found', message: 'Магазин не найден' } },
        }),
      ),
    ).toBe('Магазин не найден');
    expect(errorMessage(new HttpErrorResponse({ status: 502, error: '<html>' }))).toContain('502');
  });

  it('falls back to a generic message for non-HTTP errors', () => {
    expect(errorMessage(new Error('boom'))).toBe('Что-то пошло не так. Попробуйте ещё раз.');
  });

  it('names districts in Russian and keeps unknown codes', () => {
    expect(districtLabel('north')).toBe('Север');
    expect(districtLabel('zarechye')).toBe('zarechye');
    expect(districtLabel(null)).toBe('');
  });

  it('builds the orders url', () => {
    expect(ordersUrl(null, 1, 20)).toBe('/api/shops/1/orders?page=1&perPage=20');
    expect(ordersUrl('new', 2, 20)).toBe('/api/shops/1/orders?page=2&perPage=20&status=new');
  });
});
