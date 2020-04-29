<?php


namespace ylPay\interfaces;

use ylPay\core\Container;

/**
 * Interface Provider
 * @package ylPay\interfaces
 */
interface Provider
{
    public function serviceProvider(Container $container);
}
