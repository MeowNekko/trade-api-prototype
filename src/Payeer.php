<?php
namespace Payeer\TradeApiPrototype;

use Exception;

class Payeer
{
    const URL           = 'https://payeer.com/api/trade/';
    const DEFAULT_PAIR  = 'BTC_USDT';

    private ?string $apiId;
    private ?string $apiSecret;
    private array $params;
    private array $errors = [];
    private array $headers = [];
    private $curl;

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
    }

    /**
     * @param string $method
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function request(string $method, array $params = []): array
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_URL, self::URL . $method);

        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HEADER, false);

        $this->headers['Content-Type'] = 'application/json';

        if ($this->apiId && $this->apiSecret) {
            $params['post']['ts'] = round(microtime(true) * 1000);;
            $this->headers['API-ID'] = $this->apiId;
            $this->headers['API-SIGN'] = $this->getSign($method . json_encode($params['post']));
        }
        if (!empty($params['post'])) {
            $post = json_encode($params['post']);
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);
        }

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);

        $response = curl_exec($this->curl);
        curl_close($this->curl);

        $result = $response ? json_decode($response, true) : '';

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
     * @param string $pair
     * @return array
     * @throws Exception
     */
    public function getInfo(string $pair): array
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
