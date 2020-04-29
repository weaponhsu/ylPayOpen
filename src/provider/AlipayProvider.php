<?php


namespace ylPay\provider;


use ylPay\core\Container;
use ylPay\functions\Alipay\Pay;
use ylPay\interfaces\Provider;

class AlipayProvider implements Provider
{
    public function serviceProvider(Container $container)
    {
        // TODO: Implement serviceProvider() method.
        $container['alipay'] = function ($container){
            return new Pay($container);
        };
    }

}
