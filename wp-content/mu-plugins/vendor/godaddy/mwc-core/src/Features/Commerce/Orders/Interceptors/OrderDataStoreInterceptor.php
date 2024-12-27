<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\AbstractDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\Handlers\OrderDataStoreHandler;

class OrderDataStoreInterceptor extends AbstractDataStoreInterceptor
{
    protected string $objectType = 'order';

    protected string $handler = OrderDataStoreHandler::class;

    protected int $priority = PHP_INT_MAX - 10;
}
