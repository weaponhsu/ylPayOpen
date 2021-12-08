<?php


namespace ylPay;


use ylPay\core\ContainerBase;
use ylPay\provider\AlipayProvider;
use ylPay\provider\AlipayTransferProvider;
use ylPay\provider\PayPalProvider;

class ylPay extends ContainerBase
{
    public function __construct($parameters = [])
    {
        parent::__construct($parameters);
    }

    protected $provider = [
        AlipayProvider::class,
        AlipayTransferProvider::class,
        PayPalProvider::class
    ];
}
