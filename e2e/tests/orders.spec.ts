import AxeBuilder from '@axe-core/playwright';
import { Page, expect, test } from '@playwright/test';
import { resetOrders } from './db';

const isMobile = (page: Page) => (page.viewportSize()?.width ?? 0) < 900;

async function shot(page: Page, name: string): Promise<void> {
  await page.screenshot({ path: `screenshots/${test.info().project.name}-${name}.png`, fullPage: true });
}

async function expectNoHorizontalScroll(page: Page): Promise<void> {
  const [scroll, client] = await page.evaluate(() => [document.documentElement.scrollWidth, document.documentElement.clientWidth]);
  expect(scroll).toBeLessThanOrEqual(client);
}

async function expectAccessible(page: Page): Promise<void> {
  const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
  expect(result.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(', ')}`)).toEqual([]);
}

async function importOrders(page: Page): Promise<void> {
  await page.getByRole('button', { name: 'Загрузить заказы' }).click();
  await expect(page.getByTestId('summary')).toBeVisible();
}

const card = (page: Page, id: string) => page.getByTestId('order').filter({ hasText: id });

test.beforeEach(async ({ page }) => {
  await resetOrders();
  await page.goto('/');
});

test('empty state before the first import', async ({ page }) => {
  await expect(page.getByRole('heading', { name: 'Заказы маркетплейса' })).toBeVisible();
  await expect(page.getByTestId('empty')).toContainText('Загрузить заказы');
  await expectNoHorizontalScroll(page);
  await expectAccessible(page);
  await shot(page, '01-empty');
});

test('double tap imports once and explains every problem', async ({ page }) => {
  const posts: string[] = [];
  page.on('request', (r) => r.method() === 'POST' && posts.push(r.url()));

  await page.getByRole('button', { name: 'Загрузить заказы' }).dblclick();
  await expect(page.getByTestId('summary')).toContainText('Обработано: 11');
  expect(posts).toHaveLength(1);

  const summary = page.getByTestId('summary');
  await expect(summary).toContainText('Создан: 7');
  await expect(summary).toContainText('Дубль: 1');
  await expect(summary).toContainText('Отклонён: 3');

  const results = page.getByTestId('results').locator('li');
  await expect(results).toHaveCount(5);
  await expect(results.filter({ hasText: 'MP-1006' })).toContainText('Для района «zarechye» нет тарифа доставки');
  await expect(results.filter({ hasText: 'MP-1007' })).toContainText('Телефон «123» не приводится');
  await expect(results.filter({ hasText: 'MP-1008' })).toContainText('Неизвестный статус «PACKING»');
  await expect(results.filter({ hasText: 'MP-1009' })).toContainText('не совпадает с итогом площадки');
  await expect(results.filter({ hasText: 'Дубль' })).toContainText('уже встречался в этой пачке');

  await expectNoHorizontalScroll(page);
  await expectAccessible(page);
  await shot(page, '02-imported');

  await page.getByTestId('show-all').click();
  await expect(results).toHaveCount(11);
});

test('orders show delivery, totals and shop-time intervals', async ({ page }) => {
  await importOrders(page);
  await expect(page.getByTestId('order')).toHaveCount(7);

  const mp1002 = card(page, 'MP-1002');
  await expect(mp1002).toContainText('Север, пр. Королёва, 5');
  await expect(mp1002).toContainText('10 окт., 12:00–14:00');
  await expect(mp1002).toContainText('600 ₽');
  await expect(mp1002).toContainText('срочно');
  await expect(mp1002).toContainText('5 600 ₽');

  await expect(card(page, 'MP-1003')).toContainText('6 400 ₽');
  await expect(card(page, 'MP-1004')).toContainText('Самовывоз');
  await expect(card(page, 'MP-1009')).toContainText('Нужна проверка');
  await expect(card(page, 'MP-1009')).toContainText('4 300 ₽');

  const details = card(page, 'MP-1002').getByTestId('details');
  await details.locator('summary').click();
  await expect(details).toContainText('Букет «Рассвет» × 2');
  await expect(details).toContainText('B-102');
  await expect(details).toContainText('Доставка, район «Север»');
  await expect(details).toContainText('Срочная доставка');

  const tel = mp1002.getByRole('link', { name: '+7 900 111-22-33' });
  await expect(tel).toHaveAttribute('href', 'tel:+79001112233');

  await expectNoHorizontalScroll(page);
  await expectAccessible(page);
  await shot(page, '03-orders');
});

test('status filter with counts', async ({ page }) => {
  await importOrders(page);
  const newChip = page.getByTestId('filters').getByRole('button', { name: /Новый/ });
  await expect(newChip).toContainText('4');

  await newChip.click();
  await expect(newChip).toHaveAttribute('aria-pressed', 'true');
  await expect(page.getByTestId('order')).toHaveCount(4);
  for (const id of ['MP-1001', 'MP-1003', 'MP-1004', 'MP-1009']) {
    await expect(card(page, id)).toBeVisible();
  }

  await page.getByTestId('filters').getByRole('button', { name: /В доставке/ }).click();
  await expect(page.getByTestId('empty')).toContainText('«В доставке» нет');
  await shot(page, '04-filter-empty');

  await page.getByTestId('filters').getByRole('button', { name: /Все/ }).click();
  await expect(page.getByTestId('order')).toHaveCount(7);
});

test('repeated import changes nothing', async ({ page }) => {
  await importOrders(page);
  await page.getByRole('button', { name: 'Загрузить заказы' }).click();
  await expect(page.getByTestId('summary')).toContainText('Без изменений: 7');
  await expect(page.getByTestId('order')).toHaveCount(7);
});

test('network failure is explained and retry works', async ({ page }) => {
  await page.route('**/api/shops/1/imports', (route) => route.abort('internetdisconnected'));
  await page.getByRole('button', { name: 'Загрузить заказы' }).click();
  await expect(page.getByTestId('import-error')).toContainText('Нет связи с сервером');
  await expectAccessible(page);
  await shot(page, '05-network-error');

  await page.unroute('**/api/shops/1/imports');
  await page.getByTestId('import-error').getByRole('button', { name: 'Повторить' }).click();
  await expect(page.getByTestId('summary')).toContainText('Создан: 7');
});

test('server error on the list is explained', async ({ page }) => {
  await page.route('**/api/shops/1/orders**', (route) =>
    route.fulfill({ status: 500, json: { error: { code: 'internal_error', message: 'Внутренняя ошибка сервера, попробуйте ещё раз' } } }),
  );
  await page.reload();
  await expect(page.getByTestId('list-error')).toContainText('Внутренняя ошибка сервера');
});

test('layout fits the device', async ({ page }) => {
  await importOrders(page);
  const button = page.getByRole('button', { name: 'Загрузить заказы' });
  const box = (await button.boundingBox())!;
  expect(box.height).toBeGreaterThanOrEqual(44);

  const head = page.locator('.orders-head');
  if (isMobile(page)) {
    await expect(head).toBeHidden();
    expect(box.width).toBeGreaterThan(page.viewportSize()!.width * 0.8);
    for (const chip of await page.getByTestId('filters').getByRole('button').all()) {
      expect((await chip.boundingBox())!.height).toBeGreaterThanOrEqual(36);
    }
  } else {
    await expect(head).toBeVisible();
  }
  await expectNoHorizontalScroll(page);
});
