<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\NewOrderInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\OrderAfterSaveInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\OrderDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IntegrationEnabledOnTestTrait;

class OrdersIntegration extends AbstractIntegration
{
    use IntegrationEnabledOnTestTrait;

    public const NAME = 'orders';

    /** @var class-string<ComponentContract>[] Alphabetically ordered list of components to load */
    protected array $componentClasses = [
        NewOrderInterceptor::class,
        OrderAfterSaveInterceptor::class,
        OrderDataStoreInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getIntegrationName() : string
    {
        return static::NAME;
    }
}
