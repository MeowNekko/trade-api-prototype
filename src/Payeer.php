<?php
namespace Payeer\TradeApiPrototype;

use Exception;
use GuzzleHttp\Client;

class Payeer
{
    const URL           = 'https://payeer.com/api/trade/';
    const DEFAULT_PAIR  = 'BTC_USDT';

    private ?string $apiId;
    private ?string $apiSecret;
    private array $params;
    private array $errors = [];
    private Client $client;

    /**
     * @param ?string $apiId
     * @param ?string $apiSecret
     * @param array $params
     */
    public function __construct(
        ?string $apiId = null,
        ?string $apiSecret = null,
        array $params = []
    ) {
        $this->apiId = $apiId;
        $this->apiSecret = $apiSecret;
        $this->params = $params;
        $this->client = new Client();
    }

    /**
     * @param string $method
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function request(string $method, array $params = []): array
    {
        $options['headers']['Content-Type'] = 'application/json';

        if ($this->apiId && $this->apiSecret) {
            $params['post']['ts'] = round(microtime(true) * 1000);
            $options['body'] = json_encode($params['post']);
            $options['headers']['API-ID'] = $this->apiId;
            $options['headers']['API-SIGN'] = $this->getSign($method . $options['body']);
        }
        if (!empty($params['post']) && empty($options['body'])) {
            $options['body'] = json_encode($params['post']);
        }

        $type = empty($options['body']) ? 'GET' : 'POST';
        $response = $this->client->request($type, self::URL . $method, $options);
        $result = $response->getBody() ? json_decode($response->getBody(), true) : '';

        if (!empty($result) && !$result['success']) {
            $this->errors = $result['error'] ?? [];
            throw new Exception($result['error']['code'] ?? '');
        }

        return $result ?? [];
    }

    /**
     * @return array
     */
    public function getError(): array
    {
        return $this->errors ?? [];
    }

    /**
     * @param string $data
     * @return string
     */
    private function getSign(string $data): string {
        return hash_hmac('sha256', $data, $this->apiSecret) ?? '';
    }

    /**
     * @param ?string $pair
     * @return array
     * @throws Exception
     */
    public function getInfo(?string $pair = null): array
    {
        $data = [];

        if (!empty($pair)) {
            $data['pair'] = $pair;
        }

        return $this->request('info', $data);
    }

    /**
     * @param string $pair
     * @return array
     * @throws Exception
     */
    public function getTicker(string $pair = self::DEFAULT_PAIR): array
    {
        $result = $this->request('ticker', [
            'post' => [
                'pair' => $pair
            ]
        ]);

        return $result['pairs'];
    }

    /**
     * @param string $pair
     * @return mixed
     * @throws Exception
     */
    public function getOrders(string $pair = self::DEFAULT_PAIR): array
    {
        $result = $this->request('orders', [
            'post' => [
                'pair' => $pair
            ]
        ]);

        return $result['pairs'];
    }

    /**
     * @param string $pair
     * @return array
     * @throws Exception
     */
    public function getTrades(string $pair = self::DEFAULT_PAIR): array
    {
        $result = $this->request('trades', [
            'post' => [
                'pair' => $pair
            ]
        ]);

        return $result['pairs'];
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getAccount(): array
    {
        $result = $this->request('account');

        return $result['balances'];
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function createOrder(array $params = []): array
    {
        return $this->request('order_create', [
            'post' => $params
        ]);
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getOrderStatus(array $params = []): array
    {
        $result = $this->request('order_status', [
            'post' => $params
        ]);

        return $result['order'];
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function cancelOrder(array $params = []): array
    {
        return $this->request('order_cancel', [
            'post' => $params
        ]);
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function cancelOrders(array $params = []): array
    {
        $result = $this->request('orders_cancel', [
            'post' => $params
        ]);

        return $result['items'];
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getMyOrders(array $params = []): array
    {
        $result = $this->request('my_orders', [
            'post' => $params
        ]);

        return $result['items'];
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getMyTrades(array $params = []): array
    {
        $result = $this->request('my_trades', [
            'post' => $params
        ]);

        return $result['items'];
    }
}
