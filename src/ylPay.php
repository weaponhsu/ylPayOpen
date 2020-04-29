<?php


namespace ylPay;


use ylPay\core\ContainerBase;
use ylPay\provider\AlipayProvider;

class ylPay extends ContainerBase
{
    public function __construct($parameters = [])
    {
        parent::__construct($parameters);
    }

    protected $provider = [
        AlipayProvider::class,
    ];
}
