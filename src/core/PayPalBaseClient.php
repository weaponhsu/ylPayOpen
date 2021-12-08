<?php


namespace ylPay\core;


class PayPalBaseClient
{
    protected $app;

    public $base_url = 'https://api-m.sandbox.paypal.com';

    public $client = "";

    public $secret = "";

    public function __construct(Container $app) {
        $this->app = $app;
//        $this->log_path =
    }

    public function get() {
        return $this->curlRequest($this->base_url, "GET", '', $this->app->headers);
    }

    public function post() {
        var_dump(realpath(dirname(__FILE__)));
        return $this->curlRequest($this->base_url, "POST", $this->app->params, $this->app->headers);
    }

    public function patch() {
        return $this->curlRequest($this->base_url, "PATCH", $this->app->params, $this->app->headers);
    }

    /**
     * curl 请求
     * @param $url
     * @param string $methods
     * @param string $post_fields
     * @param array $headers
     * @return bool|string
     * @throws ylPayException
     */
    public function curlRequest($url, $method = "GET", $post_fields = "", $headers = [])
    {
        if (empty($url))
            throw new \Exception("baseUrl不能为空");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $flag = 200;
        if ($method == 'PATCH') {
            array_push($headers, 'X-HTTP-Method-Override: ' . $method);
            $flag = 204;
        } else if ($method == 'POST')
            $flag = 201;

        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (is_array($post_fields) && 0 < count($post_fields))
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
        else if (is_string($post_fields) && null !== json_decode($post_fields, true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            array_push($headers, 'Content-type: application/json');
        }

        if (!empty($headers))
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $err_code = curl_errno($ch);
        if ($err_code) {
            throw new ylPayException($err_code, 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            var_dump($flag, $httpStatusCode);
            if ($httpStatusCode !== $flag) {
                // 日志记录错误信息
            }
//            if ($httpStatusCode !== $flag) {
//                throw new ylPayException($response, $httpStatusCode);
//            }
        }

        curl_close($ch);
        return $response;
    }
}