import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FIXTURE_URL } from '../orders-api';
import { OrdersStore } from '../orders-store';
import { report, result } from '../testing/order-fixtures';
import { ImportPanel } from './import-panel';

describe('ImportPanel', () => {
  let fixture: ComponentFixture<ImportPanel>;
  let http: HttpTestingController;
  let el: HTMLElement;
  let reload: ReturnType<typeof vi.fn>;

  beforeEach(async () => {
    reload = vi.fn();
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: OrdersStore, useValue: { reload } },
      ],
    });
    fixture = TestBed.createComponent(ImportPanel);
    http = TestBed.inject(HttpTestingController);
    el = fixture.nativeElement;
    await fixture.whenStable();
  });

  afterEach(() => {
    http.verify();
  });

  const button = () => el.querySelector<HTMLButtonElement>('[data-test=import]')!;

  async function clickAndRespond(body: object): Promise<void> {
    button().click();
    http.expectOne(FIXTURE_URL).flush('[{"id":"MP-1"}]');
    const post = http.expectOne('/api/shops/1/imports');
    expect(post.request.method).toBe('POST');
    expect(post.request.body).toBe('[{"id":"MP-1"}]');
    post.flush(body);
    await fixture.whenStable();
  }

  it('sends the fixture and shows only problem records by default', async () => {
    await clickAndRespond(
      report([
        result({ index: 0, externalId: 'MP-1', outcome: 'created' }),
        result({
          index: 1,
          externalId: 'MP-2',
          outcome: 'rejected',
          orderId: null,
          status: null,
          reason: {
            code: 'invalid_phone',
            message: 'Телефон «1» не приводится',
            field: 'customer.phone',
          },
        }),
        result({
          index: 2,
          externalId: 'MP-1',
          outcome: 'duplicate',
          orderId: null,
          status: null,
          notes: [{ code: 'duplicate_in_batch', message: 'Повтор' }],
        }),
      ]),
    );

    expect(el.querySelector('[data-test=summary]')!.textContent).toContain('Обработано: 3');
    const items = [...el.querySelectorAll('.result')].map((li) => li.textContent);
    expect(items).toHaveLength(2);
    expect(items[0]).toContain('Телефон «1» не приводится');
    expect(items[1]).toContain('Дубль');
    expect(reload).toHaveBeenCalledTimes(1);

    el.querySelector<HTMLButtonElement>('[data-test=show-all]')!.click();
    await fixture.whenStable();
    expect(el.querySelectorAll('.result')).toHaveLength(3);
  });

  it('filters the report by outcome chip and toggles back to problems', async () => {
    await clickAndRespond(
      report([
        result({ index: 0, externalId: 'MP-1', outcome: 'created' }),
        result({ index: 1, externalId: 'MP-2', outcome: 'created' }),
        result({
          index: 2,
          externalId: 'MP-3',
          outcome: 'rejected',
          reason: { code: 'no_items', message: 'Нет позиций', field: 'items' },
        }),
      ]),
    );
    const chip = (label: string) =>
      [...el.querySelectorAll<HTMLButtonElement>('.summary .chip')].find((b) =>
        b.textContent.includes(label),
      )!;

    chip('Создан').click();
    await fixture.whenStable();
    expect([...el.querySelectorAll('.result strong')].map((s) => s.textContent)).toEqual([
      'MP-1',
      'MP-2',
    ]);
    expect(chip('Создан').classList).toContain('active');

    chip('Создан').click();
    await fixture.whenStable();
    expect([...el.querySelectorAll('.result strong')].map((s) => s.textContent)).toEqual(['MP-3']);

    el.querySelector<HTMLButtonElement>('[data-test=show-all]')!.click();
    await fixture.whenStable();
    expect(el.querySelectorAll('.result')).toHaveLength(3);
    [...el.querySelectorAll<HTMLButtonElement>('.show-all')]
      .find((b) => b.textContent.includes('Только проблемные'))!
      .click();
    await fixture.whenStable();
    expect(el.querySelectorAll('.result')).toHaveLength(1);
  });

  it('labels a record without id by its position', async () => {
    await clickAndRespond(
      report([
        result({
          index: 4,
          externalId: null,
          outcome: 'rejected',
          orderId: null,
          status: null,
          reason: { code: 'missing_field', message: 'Нет id', field: 'id' },
        }),
      ]),
    );
    expect(el.querySelector('.result strong')!.textContent).toBe('Запись №5');
  });

  it('says so when there are no problems', async () => {
    await clickAndRespond(report([result({ outcome: 'unchanged' })]));
    expect(el.querySelector('[data-test=no-problems]')).not.toBeNull();
  });

  it('shows status transitions for updated orders', async () => {
    await clickAndRespond(
      report([
        result({
          outcome: 'updated',
          status: 'delivered',
          previousStatus: 'delivering',
          notes: [],
        }),
      ]),
    );
    el.querySelector<HTMLButtonElement>('[data-test=show-all]')!.click();
    await fixture.whenStable();
    expect(el.querySelector('.result')!.textContent).toContain('В доставке → Доставлен');
  });

  it('ignores repeated clicks while an import is in flight', async () => {
    button().click();
    await fixture.whenStable();
    expect(button().disabled).toBe(true);
    fixture.componentInstance.run();
    http.expectOne(FIXTURE_URL).flush('[]');
    http.expectOne('/api/shops/1/imports').flush(report([]));
    await fixture.whenStable();
    expect(button().disabled).toBe(false);
  });

  it('shows an API error and allows retry', async () => {
    button().click();
    http.expectOne(FIXTURE_URL).flush('[]');
    http
      .expectOne('/api/shops/1/imports')
      .flush(
        { error: { code: 'invalid_batch', message: 'Ожидается массив заказов' } },
        { status: 422, statusText: 'Unprocessable' },
      );
    await fixture.whenStable();

    const alert = el.querySelector('[data-test=import-error]')!;
    expect(alert.textContent).toContain('Ожидается массив заказов');
    alert.querySelector<HTMLButtonElement>('button')!.click();
    http.expectOne(FIXTURE_URL);
  });

  it('shows a network error', async () => {
    button().click();
    http.expectOne(FIXTURE_URL).error(new ProgressEvent('error'));
    await fixture.whenStable();
    expect(el.querySelector('[data-test=import-error]')!.textContent).toContain('Нет связи');
  });
});
