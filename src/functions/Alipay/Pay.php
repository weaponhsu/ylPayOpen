<?php


namespace ylPay\functions\Alipay;

use ylPay\core\BaseClient;
use ylPay\core\ylPayException;

/**
 * Class Pay
 * @package ylPay\functions\Alipay
 */
class Pay extends BaseClient
{
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
//        if (false === $this->verify($this->app->sign, $string_to_be_signed, $this->app->alipay_rsa_public_key_path))
//            throw new ylPayException("签验失败", 400);

        return $this->app->params;
    }

    protected function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    /**
     * 生成待签名字符串
     * @return string
     */
    protected function getSignContent()
    {
        ksort($this->app->params);

        $string_to_be_signed = "";
        $i = 0;
        foreach ($this->app->params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $string_to_be_signed .= "$k" . "=" . "$v";
                } else {
                    $string_to_be_signed .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $string_to_be_signed;
    }

    /**
     * 验签
     * @param $signature
     * @param $string_to_be_signed
     * @param string $rsa_public_key_path
     * @param string $sign_type
     * @return bool
     * @throws ylPayException
     */
    protected function verify($signature, $string_to_be_signed, $rsa_public_key_path = '', $sign_type = 'RSA') {
        try {
            if ($this->checkEmpty($rsa_public_key_path) || ! file_exists($rsa_public_key_path)) {
                $pubKey = $this->app->alipay_rsa_public_key;
                $res = "-----BEGIN PUBLIC KEY-----\n" .
                    wordwrap($pubKey, 64, "\n", true) .
                    "\n-----END PUBLIC KEY-----";
            } else {
                //读取公钥文件
                $pubKey = file_get_contents($rsa_public_key_path);
                //转换为openssl格式密钥
                $res = openssl_get_publickey($pubKey);
            }

            //调用openssl内置方法验签，返回bool值

            if ("RSA2" == $sign_type) {
                $result = (openssl_verify($string_to_be_signed, base64_decode($signature), $res, OPENSSL_ALGO_SHA256) === 1);
            } else {
                $result = (openssl_verify($string_to_be_signed, base64_decode($signature), $res) === 1);
            }

            if (!$this->checkEmpty($rsa_public_key_path)) {
                //释放资源
                openssl_free_key($res);
            }

            return $result;

        } catch (\Exception $e) {
            throw new ylPayException('支付宝RSA公钥错误。请检查公钥文件格式是否正确', 400);
        }
    }

    /**
     * 签名
     * @param string $data 待签名的数据
     * @return string
     * @throws ylPayException
     */
    protected function sign($data) {
        try {
            if ($this->checkEmpty($this->app->alipay_rsa_private_key_path)) {
                $pri_key = $this->app->alipay_rsa_private_key;
                $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                    wordwrap($pri_key, 64, "\n", true) .
                    "\n-----END RSA PRIVATE KEY-----";
            } else {
                $pri_key = file_get_contents($this->app->alipay_rsa_private_key_path);
                $res = openssl_get_privatekey($pri_key);
            }

            if ("RSA2" == $this->app->params['sign_type']) {
                openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
            } else {
                openssl_sign($data, $sign, $res);
            }

            if (!$this->checkEmpty($this->app->alipay_rsa_private_key_path)) {
                openssl_free_key($res);
            }
            $sign = base64_encode($sign);

            return $sign;

        } catch (\Exception $e) {
            throw new ylPayException('您使用的私钥格式错误，请检查RSA私钥配置', 400);
        }
    }

    public function alipayTradeClose() {
        return $this;
    }

}
