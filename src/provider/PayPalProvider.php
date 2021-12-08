<?php


namespace ylPay\provider;


use ylPay\core\Container;
use ylPay\functions\PayPal\Pay;
use ylPay\interfaces\Provider;

class PayPalProvider implements Provider
{
    public function serviceProvider(Container $container)
    {
        // TODO: Implement serviceProvider() method.
        $container['paypal'] = function ($container){
            return new Pay($container);
        };
    }

}