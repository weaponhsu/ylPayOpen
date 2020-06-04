<?php


namespace ylPay\functions\Alipay\Transfer;


use ylPay\core\AliPayBaseClient;
use ylPay\core\ylPayException;

class fundTrans extends AliPayBaseClient
{
    /**
     * 支付宝转账到个人支付宝 通过邮箱或手机号码指定收款支付宝账号
     * B2C红包
     * @throws ylPayException
     */
    public function alipayFundTransUniTransfer() {
        // 添加公共参数
        $params = [
            'app_id' => $this->app->app_id,
            'method' => 'alipay.fund.trans.uni.transfer',
            'format' => 'json',
            'timestamp' => date("Y-m-d H:i:s"),
            'charset' => 'UTF-8',
            'app_cert_sn' => $this->app->alipay_transfer_cert_sn,
            'alipay_root_cert_sn' => $this->app->alipay_transer_root_cert_sn
        ];
        // 合并参数
        $this->app->params = array_merge($this->app->params, $params);

        // 签名
        $string_to_be_signed = $this->getSignContent($this->app->params);
        $this->app->params['sign'] = $this->sign($string_to_be_signed);

        $resp = $this->post();

        $res = json_decode($resp, true);

        if ($res['alipay_fund_trans_uni_transfer_response']['code'] !== '10000')
            throw new ylPayException($res['alipay_fund_trans_uni_transfer_response']['sub_msg'], 400);

        return [
            // 商户订单号
            'out_biz_no' => $res['alipay_fund_trans_uni_transfer_response']['out_biz_no'],
            // 支付宝转账订单号
            'order_id' => $res['alipay_fund_trans_uni_transfer_response']['order_id'],
            // 支付宝支付资金流水号
            'pay_fund_order_id' => $res['alipay_fund_trans_uni_transfer_response']['pay_fund_order_id'],
            // 转账单据状态。
            // SUCCESS：成功（对转账到银行卡的单据, 该状态可能变为退票[REFUND]状态）；
            // FAIL：失败（具体失败原因请参见error_code以及fail_reason返回值）；
            // DEALING：处理中；
            // REFUND：退票；
            'status' => $res['alipay_fund_trans_uni_transfer_response']['status'],
            // trans_date
            'trans_date' => $res['alipay_fund_trans_uni_transfer_response']['trans_date']
        ];
    }

    /**
     * 异步回调 校验app_id+校验seller_id+验签
     * @return mixed
     * @throws ylPayException
     */
    public function getAlipayNotifyResponse() {
        // 校验异步回调的app_id与设置的app_id是否一致
//        if ($this->app->app_id != $this->app->params['app_id'])
//            throw new ylPayException("app_id不一致", 400);

        // 验签
        $string_to_be_signed = $this->getSignContent();
        // 将cert的支付宝公钥读取出来生成字符串赋值给alipay_rsa_public_key
        if (false === $this->verify($this->app->sign, $string_to_be_signed, $this->app->alipay_rsa_public_key, $this->app->sign_type))
            throw new ylPayException("签验失败", 400);

        return $this->app->params;
    }

}
