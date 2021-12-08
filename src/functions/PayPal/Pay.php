<?php


namespace ylPay\functions\PayPal;


use ylPay\core\PayPalBaseClient;

class Pay extends PayPalBaseClient
{
    /**
     * 获取token
     * @return string
     */
    public function getAccessToken(){
        $this->base_url = $this->app->base_url . "/v1/oauth2/token";
        $this->app->headers = [
            "Content-type: application/x-www-form-urlencoded",
            'Authorization: Basic ' .  base64_encode($this->app->paypal_client . ":" . $this->app->paypal_secret)
        ];
        $this->app->params = [
            'grant_type' => 'client_credentials'
        ];

        $resp = $this->post();
        $res = json_decode($resp, true);

        return $res['token_type'] . " " . $res['access_token'];
    }

    /**
     * 支付
     * @return mixed
     */
    public function checkoutOrders() {
        $this->base_url = $this->app->base_url .  "/v2/checkout/orders";
        $this->app->headers = [
            "Content-type: application/json",
            'Authorization: ' . $this->app->access_token
        ];

        $resp = $this->post();
        $res = json_decode($resp, true);

        $pay_url = null;
        if (isset($res["links"]))
            foreach ($res["links"] as $link) {
                if ($link["rel"] == "approve") {
                    $pay_url = $link["href"];
                    break;
                }
            }

        return [$res['id'], $res['status'], $pay_url];
    }

    /**
     * 查询订单状态
     * @param $order_id
     * @return mixed
     * @throws \Exception
     */
    public function showOrderDetails($order_id) {
        if (empty($order_id) || ! is_string($order_id))
            throw new \Exception("invalid order id");

        $this->base_url = $this->app->base_url . "/v2/checkout/orders/" . $order_id;

        $resp = $this->get();
        $res = json_decode($resp, true);
        $purchase_units = $res['purchase_units'][0];

        // intent = capture && status = created 订单创建尚未付款
        // intent = capture && status = approved 订单创建已付款
        // purchase_units['payments']['captures'][0]['status'] = REFUNDED 已退款
        return [
            $res['intent'], $res['status'], $purchase_units['reference_id'],
            isset($purchase_units['payments']['captures'][0]['id']) ? $purchase_units['payments']['captures'][0]['id'] : null,
            isset($purchase_units['amount']) ? $purchase_units['amount'] : null,
            isset($purchase_units['payee']) ? $purchase_units['payee'] : null,
            isset($purchase_units['shipping']) ? $purchase_units['shipping'] : null,
            isset($purchase_units['payments']) ? $purchase_units['payments'] : null
        ];
    }

    /**
     * 修改订单信息
     * @param $order_id
     * @return mixed
     * @throws \Exception
     */
    public function updateOrder($order_id) {
        if (empty($order_id) || ! is_string($order_id))
            throw new \Exception("invalid order id");

        $this->base_url = $this->app->base_url . "/v2/checkout/orders/" . $order_id;

        $resp = $this->patch();
        return json_decode($resp, true);
    }

    /**
     * 付款 必须创建订单+用户在paypal支付成功后 调用方可成功
     * @param $order_id
     * @return array
     * @throws \Exception
     */
    public function orderCapture($order_id) {
        if (empty($order_id) || ! is_string($order_id))
            throw new \Exception("invalid order id");

        $this->app->params = "{}";
        $this->app->headers = [
            "Content-type: application/json",
            'Authorization: ' . $this->app->access_token
        ];

        $this->base_url = $this->app->base_url . "/v2/checkout/orders/" . $order_id . "/capture";

        $resp = $this->post();
        $res = json_decode($resp, true);

        if (isset($res['name']) && $res['name'] == "UNPROCESSABLE_ENTITY" &&
            isset($res['details']) && count($res['details']) > 0) {
            return [
                null, null, $res['details'][0]['issue']
            ];
        }

        $purchase_units = $res['purchase_units'][0];
        return [
            $purchase_units['reference_id'],
            $purchase_units['payments']['captures'][0]['id'],
            $purchase_units['payments']['captures'][0]['status']
        ];
    }

    /**
     * 退款
     * @param $order_id
     * @return mixed
     * @throws \Exception
     */
    public function refund($order_id) {
        if (empty($order_id) || ! is_string($order_id))
            throw new \Exception("invalid order id");

        $this->base_url = $this->app->base_url . "/v2/payments/captures/" . $order_id . "/refund";

//        $this->app->params = "{}";
        $this->app->headers = [
            "Content-type: application/json",
            'Authorization: ' . $this->app->access_token
        ];

        $resp = $this->post();
        return json_decode($resp, true);
    }

}