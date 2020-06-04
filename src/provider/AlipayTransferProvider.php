<?php


namespace ylPay\provider;


use ylPay\core\Container;
use ylPay\functions\Alipay\Transfer\fundTrans;
use ylPay\interfaces\Provider;

/**
 * 支付宝转账
 * Class AlipayTransferProvider
 * @package ylPay\provider
 */
class AlipayTransferProvider implements Provider
{

    public function serviceProvider(Container $container)
    {
        // TODO: Implement serviceProvider() method.
        $container['alipay_transfer'] = function ($container){
            return new fundTrans($container);
        };
    }
}
