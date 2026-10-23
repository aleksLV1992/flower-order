import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { OrdersStore } from '../orders-store';
import { order, page } from '../testing/order-fixtures';
import { OrderList } from './order-list';

describe('OrderList', () => {
  let fixture: ComponentFixture<OrderList>;
  let http: HttpTestingController;
  let el: HTMLElement;

  async function respond(url: string, body: object): Promise<void> {
    TestBed.tick();
    http.expectOne(url).flush(body);
    await fixture.whenStable();
  }

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    fixture = TestBed.createComponent(OrderList);
    http = TestBed.inject(HttpTestingController);
    el = fixture.nativeElement;
  });

  afterEach(() => {
    http.verify();
  });

  it('renders orders with delivery, totals and review flag', async () => {
    await respond(
      '/api/shops/1/orders?page=1&perPage=20',
      page([
        order({
          id: 2,
          externalId: 'MP-2',
          deliveryType: 'pickup',
          district: null,
          address: null,
          interval: null,
          delivery: { cost: 0, breakdown: [] },
          grandTotal: 280000,
        }),
        order({
          id: 1,
          needsReview: true,
          reviewReasons: [{ code: 'total_mismatch', message: 'Сумма не совпадает' }],
          delivery: {
            cost: 50000,
            breakdown: [
              { code: 'base', amount: 30000, label: 'База' },
              { code: 'urgency', amount: 20000, label: 'Срочная доставка' },
            ],
          },
          grandTotal: 330000,
        }),
      ]),
    );

    const cards = el.querySelectorAll('[data-test=order]');
    expect(cards).toHaveLength(2);
    expect(cards[0].textContent).toContain('Самовывоз');
    expect(cards[0].textContent).toContain('2 800 ₽');
    expect(cards[1].textContent).toContain('Нужна проверка');
    expect(cards[1].textContent).toContain('Сумма не совпадает');
    expect(cards[1].textContent).toContain('срочно');
    expect(cards[1].textContent).toContain('3 300 ₽');
    expect(cards[1].textContent).toContain('20 окт., 14:00–16:00');
  });

  it('shows items and price breakdown in an expandable block', async () => {
    await respond(
      '/api/shops/1/orders?page=1&perPage=20',
      page([
        order({
          id: 1,
          district: 'north',
          items: [
            {
              sku: 'B-109',
              name: 'Букет «Классика»',
              qty: 2,
              unitPrice: 180000,
              lineTotal: 360000,
            },
          ],
          itemsTotal: 360000,
          declaredTotal: 300000,
          delivery: {
            cost: 70000,
            breakdown: [{ code: 'base', amount: 70000, label: 'Доставка, район «Пригород»' }],
          },
        }),
        order({
          id: 2,
          externalId: 'MP-2',
          deliveryType: 'pickup',
          district: null,
          address: null,
          interval: null,
          delivery: { cost: 0, breakdown: [] },
        }),
      ]),
    );

    const [delivery, pickup] = [...el.querySelectorAll('[data-test=details]')].map((d) =>
      d.textContent.replace(/\s+/g, ' '),
    );
    expect(delivery).toContain('Букет «Классика» × 2');
    expect(delivery).toContain('B-109');
    expect(delivery).toContain('3 600 ₽');
    expect(delivery).toContain('Доставка, район «Пригород»');
    const declared = [...el.querySelectorAll('[data-test=details] li')].find((li) =>
      li.textContent.includes('Итог площадки'),
    )!;
    expect([...declared.querySelectorAll('span')].map((x) => x.textContent)).toEqual([
      'Итог площадки (не используется)',
      '3 000 ₽',
    ]);
    expect(pickup).toContain('Самовывоз');
    expect(pickup).not.toContain('Итог площадки');
    expect(el.querySelector('[data-test=order]')!.textContent).toContain('Север, ул. Ленина, 1');
  });

  it('marks the active filter for assistive technologies and keeps the shop name in the store', async () => {
    await respond('/api/shops/1/orders?page=1&perPage=20', page([order({})]));
    expect(el.querySelector('[data-test=filters] .chip')!.getAttribute('aria-pressed')).toBe(
      'true',
    );
    expect(el.querySelector('[data-status=new]')!.getAttribute('aria-pressed')).toBe('false');
    expect(TestBed.inject(OrdersStore).shopName()).toBe('Лавка');
  });

  it('filters by status and resets to the first page', async () => {
    await respond('/api/shops/1/orders?page=1&perPage=20', page([order({})], { total: 45 }));
    expect(el.textContent).toContain('1 из 3');

    el.querySelector<HTMLButtonElement>('.pager button:last-child')!.click();
    await respond(
      '/api/shops/1/orders?page=2&perPage=20',
      page([order({})], { total: 45, page: 2 }),
    );

    el.querySelector<HTMLButtonElement>('[data-status=delivered]')!.click();
    await respond('/api/shops/1/orders?page=1&perPage=20&status=delivered', page([]));

    expect(el.querySelector('[data-test=empty]')!.textContent).toContain('«Доставлен» нет');
  });

  it('shows the empty state before the first import', async () => {
    await respond('/api/shops/1/orders?page=1&perPage=20', page([]));
    expect(el.querySelector('[data-test=empty]')!.textContent).toContain('Загрузить заказы');
  });

  it('shows an error and retries', async () => {
    TestBed.tick();
    http
      .expectOne('/api/shops/1/orders?page=1&perPage=20')
      .flush(
        { error: { code: 'internal_error', message: 'Внутренняя ошибка' } },
        { status: 500, statusText: 'err' },
      );
    await fixture.whenStable();

    const alert = el.querySelector('[data-test=list-error]')!;
    expect(alert.textContent).toContain('Внутренняя ошибка');
    alert.querySelector<HTMLButtonElement>('button')!.click();
    await respond('/api/shops/1/orders?page=1&perPage=20', page([order({})]));
    expect(el.querySelectorAll('[data-test=order]')).toHaveLength(1);
  });
});
