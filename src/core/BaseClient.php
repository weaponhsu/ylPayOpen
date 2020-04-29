<?php


namespace ylPay\core;


class BaseClient
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


    /**
     * @throws ylPayException
     */
    /*public function sign(){
        if ($this->mode === 'production') {
            //url 因子
            if(empty($this->url_info)){
                throw new ylPayException('url因子为空，如无配置，请配置');
            }
            $arr = explode(':',$this->url_info);
            $spacename = $arr[0];
            $arr = explode('-',$arr[1]);
            $version = $arr[1];
            $apiname = $arr[0];
            $url_info = 'param2/'.$version.'/'.$spacename.'/'.$apiname.'/';

            //参数因子
            $appKey = $this->app->app_key;
            $appSecret =$this->app->app_secret;
            $apiInfo = $url_info. $appKey;//此处请用具体api进行替换
            //配置参数，请用apiInfo对应的api参数进行替换
            $code_arr = array_merge([
                'access_token' => $this->app->access_token
            ],$this->app->params);
            $aliParams = array();
            $url_pin = '';
            foreach ($code_arr as $key => $val) {
                $url_pin .=$key.'='.$val.'&';
                $aliParams[] = $key . $val;
            }
            sort($aliParams);
            $sign_str = join('', $aliParams);
            $sign_str = $apiInfo . $sign_str;

            //签名
            $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
            $this->postData  = $code_arr;
            $this->res_url =  $this->base_url.$apiInfo.'?'.$url_pin.'_aop_signature='.$code_sign;
        }
    }*/

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
        return $this->curlRequest($this->app->base_url, $this->app->params);
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

}
