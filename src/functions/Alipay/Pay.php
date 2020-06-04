<?php


namespace ylPay\functions\Alipay;

use ylPay\core\AliPayBaseClient;
use ylPay\core\ylPayException;

/**
 * Class Pay
 * @package ylPay\functions\Alipay
 */
class Pay extends AliPayBaseClient
{
    /**
     * wap支付
     * @return string
     * @throws ylPayException
     */
    public function alipayTradeWapPay() {
        // 添加公共参数
        $params = [
            'app_id' => $this->app->app_id,
            'method' => 'alipay.trade.wap.pay',
            'format' => 'json',
            'timestamp' => date("Y-m-d H:i:s"),
            'charset' => 'UTF-8'
        ];
        // 合并参数
        $this->app->params = array_merge($this->app->params, $params);

        // 签名
        $string_to_be_signed = $this->getSignContent();
        $this->app->params['sign'] = $this->sign($string_to_be_signed);

        return $this->app->base_url . '?' . http_build_query($this->app->params);
    }

    /**
     * 支付宝app支付
     * @return string
     * @throws ylPayException
     */
    public function alipayTradeAppPay() {
        // 添加公共参数
        $params = [
            'app_id' => $this->app->app_id,
            'method' => 'alipay.trade.app.pay',
            'format' => 'json',
            'timestamp' => date("Y-m-d H:i:s"),
            'charset' => 'UTF-8'
        ];
        // 合并参数
        $this->app->params = array_merge($this->app->params, $params);

        // 签名
        $string_to_be_signed = $this->getSignContent();
        $this->app->params['sign'] = $this->sign($string_to_be_signed);

        return http_build_query($this->app->params);
    }

    /**
     * 异步回调 校验app_id+校验seller_id+验签
     * @return mixed
     * @throws ylPayException
     */
    public function getAlipayNotifyResponse() {
        // 校验异步回调的app_id与设置的app_id是否一致
        if ($this->app->app_id != $this->app->params['app_id'])
            throw new ylPayException("app_id不一致", 400);

        // 校验异步回调的seller_id与设置的seller_id是否一致
        if ($this->app->alipay_seller_id != $this->app->params['seller_id'])
            throw new ylPayException("非法seller_id", 400);

        // 验签
        $string_to_be_signed = $this->getSignContent();
        if (false === $this->verify($this->app->sign, $string_to_be_signed, $this->app->alipay_rsa_public_key_path, $this->app->sign_type))
            throw new ylPayException("签验失败", 400);

        return $this->app->params;
    }

    public function alipayTradeClose() {
        return $this;
    }

}
