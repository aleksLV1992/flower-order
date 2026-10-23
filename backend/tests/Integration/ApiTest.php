<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\OrderStatus;
use App\Domain\Shop\Shop;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class ApiTest extends DatabaseTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        self::resetOrders();
    }

    /**
     * @return array<string, mixed>
     *
     * @phpstan-impure
     */
    private function json(): array
    {
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 64, \JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);

        /** @var array<string, mixed> $data */
        return $data;
    }

    private function postImport(string $body, int $shop = 1): void
    {
        $this->client->request('POST', "/api/shops/{$shop}/imports", server: ['CONTENT_TYPE' => 'application/json'], content: $body);
    }

    public function testImportFixture(): void
    {
        $this->postImport((string) file_get_contents(self::FIXTURE));

        self::assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertSame(['total' => 11, 'created' => 7, 'updated' => 0, 'unchanged' => 0, 'duplicate' => 1, 'rejected' => 3], $data['summary']);

        $rejected = $data['results'][5];
        $this->assertSame('MP-1006', $rejected['externalId']);
        $this->assertSame('rejected', $rejected['outcome']);
        $this->assertSame(['code' => 'unknown_district', 'message' => 'Для района «zarechye» нет тарифа доставки', 'field' => 'delivery.district'], $rejected['reason']);

        $created = $data['results'][0];
        $this->assertIsInt($created['orderId']);
        $this->assertSame('new', $created['status']);
        $this->assertSame('total_mismatch', $data['results'][8]['notes'][0]['code']);
    }

    public function testImportRejectsBareArray(): void
    {
        $this->postImport(json_encode(self::fixture(), \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);
        $this->assertSame('invalid_batch', $this->json()['error']['code']);
    }

    public function testImportErrors(): void
    {
        $this->postImport('{not json');
        self::assertResponseStatusCodeSame(400);
        $this->assertSame('invalid_json', $this->json()['error']['code']);

        $this->postImport('{"foo": 1}');
        self::assertResponseStatusCodeSame(422);
        $this->assertSame('invalid_batch', $this->json()['error']['code']);

        $this->postImport(json_encode(['orders' => array_fill(0, 1001, [])], \JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(422);
        $this->assertSame('batch_too_large', $this->json()['error']['code']);

        $this->postImport('{"orders": []}', 999);
        self::assertResponseStatusCodeSame(404);
        $this->assertSame('shop_not_found', $this->json()['error']['code']);
    }

    public function testEmptyBatch(): void
    {
        $this->postImport('{"orders": []}');
        self::assertResponseIsSuccessful();
        $this->assertSame(0, $this->json()['summary']['total']);
    }

    public function testOrderList(): void
    {
        $this->postImport((string) file_get_contents(self::FIXTURE));

        $this->client->request('GET', '/api/shops/1/orders');
        self::assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertSame(7, $data['total']);
        $this->assertSame(['new' => 4, 'accepted' => 1, 'delivering' => 0, 'delivered' => 1, 'cancelled' => 1], $data['statusCounts']);
        $this->assertSame(['MP-1010', 'MP-1009', 'MP-1005', 'MP-1004', 'MP-1003', 'MP-1002', 'MP-1001'], array_column($data['items'], 'externalId'));
        $this->assertSame('Europe/Moscow', $data['shop']['timezone']);

        $mp1002 = $data['items'][5];
        $this->assertSame(500000, $mp1002['itemsTotal']);
        $this->assertSame(['cost' => 60000, 'breakdown' => [
            ['code' => 'base', 'label' => 'Доставка, район «Север»', 'amount' => 40000],
            ['code' => 'urgency', 'label' => 'Срочная доставка', 'amount' => 20000],
        ]], $mp1002['delivery']);
        $this->assertSame(560000, $mp1002['grandTotal']);
        $this->assertSame(['from' => '2026-10-10T09:00:00+00:00', 'to' => '2026-10-10T11:00:00+00:00'], $mp1002['interval']);
        $this->assertSame('2026-10-10T08:00:00+00:00', $mp1002['placedAt']);

        $this->assertSame([
            ['sku' => 'B-101', 'name' => 'Букет «Нежность»', 'qty' => 1, 'unitPrice' => 320000, 'lineTotal' => 320000],
        ], $data['items'][6]['items']);

        $mp1009 = $data['items'][1];
        $this->assertTrue($mp1009['needsReview']);
        $this->assertSame(300000, $mp1009['declaredTotal']);
    }

    public function testOrderListFilterAndPagination(): void
    {
        $this->postImport((string) file_get_contents(self::FIXTURE));

        $this->client->request('GET', '/api/shops/1/orders?status=new');
        $this->assertSame(['MP-1009', 'MP-1004', 'MP-1003', 'MP-1001'], array_column($this->json()['items'], 'externalId'));
        $this->assertSame(4, $this->json()['total']);

        $this->client->request('GET', '/api/shops/1/orders?perPage=2&page=2');
        $data = $this->json();
        $this->assertSame(['MP-1005', 'MP-1004'], array_column($data['items'], 'externalId'));
        $this->assertSame([2, 2, 7], [$data['page'], $data['perPage'], $data['total']]);

        $this->client->request('GET', '/api/shops/1/orders?page=99');
        $this->assertSame([], $this->json()['items']);

        $this->client->request('GET', '/api/shops/1/orders?status=delivering');
        $this->assertSame(0, $this->json()['total']);
    }

    public function testOrderListErrors(): void
    {
        $this->client->request('GET', '/api/shops/1/orders?status=DONE');
        self::assertResponseStatusCodeSame(400);
        $this->assertSame('invalid_query', $this->json()['error']['code']);
        $this->assertStringContainsString('status', (string) $this->json()['error']['message']);

        $this->client->request('GET', '/api/shops/1/orders?perPage=1000');
        self::assertResponseStatusCodeSame(400);
        $this->assertSame('invalid_query', $this->json()['error']['code']);

        $this->client->request('GET', '/api/shops/1/orders?page=0');
        self::assertResponseStatusCodeSame(400);

        $this->client->request('GET', '/api/shops/999/orders');
        self::assertResponseStatusCodeSame(404);
    }

    public function testUnexpectedFailureIsGeneric500WithoutDetails(): void
    {
        self::getContainer()->set(OrderRepository::class, new class implements OrderRepository {
            public function add(Order $order): void
            {
            }

            public function findByExternalIds(Shop $shop, array $externalIds): array
            {
                return [];
            }

            public function page(Shop $shop, ?OrderStatus $status, int $page, int $perPage): array
            {
                throw new \RuntimeException('SQLSTATE[HY000]: secret connection details');
            }

            public function countByStatus(Shop $shop): array
            {
                return [];
            }
        });

        $this->client->request('GET', '/api/shops/1/orders');

        $this->assertResponseStatusCodeSame(500);
        $this->assertSame('internal_error', $this->json()['error']['code']);
        $this->assertStringNotContainsString('SQLSTATE', (string) $this->client->getResponse()->getContent());
    }

    public function testNonApiErrorsAreLeftToSymfony(): void
    {
        $this->client->request('GET', '/not-an-api-route');

        $this->assertResponseStatusCodeSame(404);
        $this->assertStringStartsNotWith('application/json', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testUnknownRouteIsJson(): void
    {
        $this->client->request('GET', '/api/nope');
        self::assertResponseStatusCodeSame(404);
        $this->assertSame('http_404', $this->json()['error']['code']);
    }
}
