<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Interceptors\CustomerDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Interceptors\GuestCustomerOrderInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Interceptors\NewRegisteredCustomerInterceptor;

class CustomersIntegration extends AbstractIntegration
{
    public const NAME = 'customers';

    /** @var class-string<ComponentContract>[] alphabetically ordered list of components to load */
    protected array $componentClasses = [
        CustomerDataStoreInterceptor::class,
        GuestCustomerOrderInterceptor::class,
        NewRegisteredCustomerInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getIntegrationName() : string
    {
        return self::NAME;
    }
}
