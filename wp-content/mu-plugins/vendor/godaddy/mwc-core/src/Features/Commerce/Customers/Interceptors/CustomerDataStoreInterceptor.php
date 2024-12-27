<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Interceptors;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Interceptors\Handlers\CustomerDataStoreHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\AbstractDataStoreInterceptor;

class CustomerDataStoreInterceptor extends AbstractDataStoreInterceptor
{
    protected string $objectType = 'customer';

    protected string $handler = CustomerDataStoreHandler::class;

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        return TypeHelper::bool(Configuration::get('features.commerce.integrations.customers.overrides.enabled'), false);
    }
}
