import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { App } from './app';
import { FIXTURE_URL } from './orders/orders-api';
import { page, report } from './orders/testing/order-fixtures';

describe('App', () => {
  it('reloads the order list after a successful import', async () => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    const fixture = TestBed.createComponent(App);
    const http = TestBed.inject(HttpTestingController);
    const el: HTMLElement = fixture.nativeElement;
    const listUrl = '/api/shops/1/orders?page=1&perPage=20';

    TestBed.tick();
    http.expectOne(listUrl).flush(page([]));
    await fixture.whenStable();
    expect(el.querySelector('h1')!.textContent).toContain('Заказы маркетплейса');

    el.querySelector<HTMLButtonElement>('[data-test=import]')!.click();
    http.expectOne(FIXTURE_URL).flush('[]');
    http.expectOne('/api/shops/1/imports').flush(report([]));
    TestBed.tick();

    http.expectOne(listUrl).flush(page([]));
    http.verify();
  });
});
