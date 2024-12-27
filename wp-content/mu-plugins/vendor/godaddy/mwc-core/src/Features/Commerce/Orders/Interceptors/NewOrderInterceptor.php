<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\Handlers\NewOrderHandler;

class NewOrderInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('woocommerce_new_order')
            ->setHandler([NewOrderHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->setPriority(PHP_INT_MIN)
            ->execute();
    }
}
