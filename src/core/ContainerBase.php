<?php


namespace ylPay\core;


/**
 * Class ContainerBase
 * @package ylAlibaba\core
 */
class ContainerBase extends Container
{
    protected $provider = [];

    public $params = [];

    public $base_url;

    public $sign = '';
    public $sign_type = '';

    public $app_id = '';
    public $alipay_rsa_private_key = '';
    public $alipay_rsa_private_key_path = '';
    public $alipay_rsa_public_key = '';
    public $alipay_rsa_public_key_path = '';
    public $alipay_seller_id = '';
    public $charset = 'utf-8';
//    public $alipay_version = "1.0";
//    public $alipay_format = 'json';
//    public $alipay_sign_type = 'RSA2';
//    public $alipay_timestamp = '';
//    public $alipay_sdk = "alipay-sdk-php-20200415";
//    public $alipay_charset = 'utf-8';


    public $app_secret = '';

    public $access_token = '';

    public function __construct($params =array())
    {
        $this->params = $params;

        $provider_callback = function ($provider) {
            $obj = new $provider;
            $this->serviceRegister($obj);
        };
        //注册
        array_walk($this->provider, $provider_callback);
    }

    public function __get($id) {
        return $this->offsetGet($id);
    }

    /**
     * @param mixed $base_url
     */
    public function setBaseUrl($base_url)
    {
        $this->base_url = $base_url;
    }

    /**
     * @param string $app_id
     */
    public function setAppId($app_id)
    {
        $this->app_id = $app_id;
    }

    /**
     * @param string $alipay_rsa_private_key
     */
    public function setAlipayRsaPrivateKey($alipay_rsa_private_key)
    {
        $this->alipay_rsa_private_key = $alipay_rsa_private_key;
    }

    /**
     * @param string $alipay_rsa_public_key
     */
    public function setAlipayRsaPublicKey($alipay_rsa_public_key)
    {
        $this->alipay_rsa_public_key = $alipay_rsa_public_key;
    }

    /**
     * @param string $alipay_rsa_private_key_path
     */
    public function setAlipayRsaPrivateKeyPath($alipay_rsa_private_key_path)
    {
        $this->alipay_rsa_private_key_path = $alipay_rsa_private_key_path;
    }

    /**
     * @param string $alipay_rsa_public_key_path
     */
    public function setAlipayRsaPublicKeyPath($alipay_rsa_public_key_path)
    {
        $this->alipay_rsa_public_key_path = $alipay_rsa_public_key_path;
    }

    /**
     * @param string $sign
     */
    public function setSign($sign)
    {
        $this->sign = $sign;
    }

    /**
     * @param string $sign_type
     */
    public function setSignType($sign_type)
    {
        $this->sign_type = $sign_type;
    }

    /**
     * @param string $alipay_seller_id
     */
    public function setAlipaySellerId($alipay_seller_id)
    {
        $this->alipay_seller_id = $alipay_seller_id;
    }
}
