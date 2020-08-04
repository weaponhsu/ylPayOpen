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

    public $alipay_transfer_rsa_private_key = '';
    public $alipay_transfer_rsa_public_key = '';
    public $alipay_app_cert = '';
    public $alipay_transfer_cert_sn = '';
    public $alipay_transer_root_cert_sn = '';
    public $alipay_root_cert_content = '';


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


    /* ------------------ 资金类接口所需的方法 ----------------------- */
    protected function array2string($array)
    {
        $string = [];
        if ($array && is_array($array)) {
            foreach ($array as $key => $value) {
                $string[] = $key . '=' . $value;
            }
        }
        return implode(',', $string);
    }

    /**
     * 从证书中提取公钥
     * @param $cert
     * @return mixed
     */
    public function setTransferPublicKey($certPath)
    {
        $cert = file_get_contents($certPath);
        $ssl = openssl_x509_parse($cert);
        $this->alipay_transfer_cert_sn = md5(self::array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
    }

    /**
     * 设置应用公钥
     * @param $alipay_app_cert
     * @return string|string[]
     */
    public function setAlipayAppCertSn($alipay_app_cert)
    {
        $cert = file_get_contents($alipay_app_cert);
        $ssl = openssl_x509_parse($cert);
        $this->alipay_app_cert = md5($this->array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
    }

    /**
     * 设置根证书
     * @param string $alipay_transer_root_cert_sn
     */
    public function setAlipayTranserRootCertSn($alipay_transer_root_cert_sn)
    {
        $cert = file_get_contents($alipay_transer_root_cert_sn);
        $this->alipay_root_cert_content = $cert;
        $array = explode("-----END CERTIFICATE-----", $cert);
        $this->alipay_transer_root_cert_sn = null;
        for ($i = 0; $i < count($array) - 1; $i++) {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            if(strpos($ssl[$i]['serialNumber'],'0x') === 0){
                $ssl[$i]['serialNumber'] = $this->hex2dec($ssl[$i]['serialNumber']);
            }
            if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
                if ($this->alipay_transer_root_cert_sn == null) {
                    $this->alipay_transer_root_cert_sn = md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {

                    $this->alipay_transer_root_cert_sn = $this->alipay_transer_root_cert_sn . "_" . md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
    }

    /**
     * 0x转高精度数字
     * @param $hex
     * @return int|string
     */
    protected function hex2dec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return round($dec,0);
    }
}
