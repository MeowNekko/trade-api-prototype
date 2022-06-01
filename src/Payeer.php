<?php

namespace Payeer\TradeApiPrototype;

use Exception;

class Payeer
{
    const DEFAULT_PAIR  = 'BTC_USDT';

    private array $arParams = [];
    private array $errors = [];


    /**
     * @param array $params
     */
    public function __construct(
        $params = []
    ) {
        $this->arParams = $params;
    }

    /**
     * @param array $req
     * @return mixed
     * @throws Exception
     */
    private function Request($req = [])
    {
        $msec = round(microtime(true) * 1000);
        $req['post']['ts'] = $msec;

        $post = json_encode($req['post']);

        $sign = hash_hmac('sha256', $req['method'].$post, $this->arParams['key']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payeer.com/api/trade/".$req['method']);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "API-ID: ".$this->arParams['id'],
            "API-SIGN: ".$sign
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $arResponse = json_decode($response, true);

        if ($arResponse['success'] !== true)
        {
            $this->errors = $arResponse['error'];
            throw new Exception($arResponse['error']['code']);
        }

        return $arResponse;
    }

    /**
     * @return array
     */
    public function getError(): array
    {
        return $this->errors;
    }

    /**
     * @param string $pair
     * @return array
     * @throws Exception
     */
    public function getInfo(string $pair): array
    {
        $res = $this->Request([
            'method' => 'info',
        ]);

        return $res;
    }

    /**
     * @param string $pair
     * @return mixed
     * @throws Exception
     */
    public function getOrders(string $pair = self::DEFAULT_PAIR): array
    {
        $res = $this->Request([
            'method' => 'orders',
            'post' => array(
                'pair' => $pair,
            ),
        ]);

        return $res['pairs'];
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getAccount(): array
    {
        $res = $this->Request([
            'method' => 'account',
        ]);

        return $res['balances'];
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function createOrder(array $params = []): array
    {
        $res = $this->Request([
            'method' => 'order_create',
            'post' => $params,
        ]);

        return $res;
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getOrderStatus(array $params = []): array
    {
        $res = $this->Request([
            'method' => 'order_status',
            'post' => $params,
        ]);

        return $res['order'];
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getMyOrders(array $params = []): array
    {
        $res = $this->Request([
            'method' => 'my_orders',
            'post' => $params,
        ]);

        return $res['items'];
    }
}
