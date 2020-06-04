<?php


namespace ylPay\core;


class AliPayBaseClient
{
    protected $app;

    public $base_url = 'http://gw.open.1688.com/openapi/';

    public $url_info;

    protected $postData;

    public $res_url;

    public $mode = 'production';

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function setMode($mode = '') {
        if (!empty($mode))
            $this->mode = $mode;
    }

    private function characet($data, $targetCharset)
    {

        if (!empty($data)) {
            $fileType = 'UTF-8';
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //				$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }


        return $data;
    }

    /**
     * get 请求方式
     * @return mixed
     * @throws ylPayException
     */
    public function get(){
//        $this->sign();
        $file =  $this->curlRequest($this->res_url,'');
        return json_decode($file,true);
    }

    /**
     * post 请求方式
     * @return mixed
     * @throws ylPayException
     */
    public function post(){
        return $this->curlRequest(! empty($this->app->base_url) ? $this->app->base_url : $this->base_url, $this->app->params);
    }

    /**
     * 设置地址
     * @param $api_name
     * @return $this
     */
    public function setApi($api_name){
        $this->url_info = $api_name;
        return $this;
    }

    /**
     * curl 请求
     * @param $url
     * @param $post_fields
     * @param string $method
     * @return bool|string
     * @throws ylPayException
     */
    public function curlRequest($url, $post_fields)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = "";
        $encodeArray = Array();
        $postMultipart = false;


        if (is_array($post_fields) && 0 < count($post_fields)) {

            foreach ($post_fields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {
                    $postBodyString .= "$k=" . urlencode($this->characet($v, $this->app->charset)) . "&";
                    $encodeArray[$k] = $this->characet($v, $this->app->charset);
                } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }

            }
            unset ($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        if (!$postMultipart) {
            $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->app->charset);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new ylPayException(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new ylPayException($response, $httpStatusCode);
            }
        }

        curl_close($ch);
        return $response;
    }

    /**
     * 检查字符串是否为空
     * @param $value
     * @return bool
     */
    public function checkEmpty($value)
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
    public function getSignContent()
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
     * 签名
     * @param string $data 待签名的数据
     * @return string
     * @throws ylPayException
     */
    public function sign($data) {
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

            if (!$this->checkEmpty($rsa_public_key_path) && ! is_string($res)) {
                //释放资源
                openssl_free_key($res);
            }

            return $result;

        } catch (\Exception $e) {
            throw new ylPayException('支付宝RSA公钥错误。请检查公钥文件格式是否正确', 400);
        }
    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    protected function addPKCS7Padding($source)
    {
        $source = trim($source);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }
}
