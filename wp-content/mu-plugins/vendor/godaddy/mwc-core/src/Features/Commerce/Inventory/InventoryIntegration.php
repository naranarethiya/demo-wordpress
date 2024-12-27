<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\AddToCartInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\CheckoutOrderInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\MapInventoryJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\ProductDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\ProductVariationDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\StockManagementSettingInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\StoreLocationInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\VariableProductDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IntegrationEnabledOnTestTrait;

class InventoryIntegration extends AbstractIntegration
{
    use IntegrationEnabledOnTestTrait;

    public const NAME = 'inventory';

    /** @var class-string<ComponentContract>[] */
    protected array $componentClasses = [
        CheckoutOrderInterceptor::class,
        ProductDataStoreInterceptor::class,
        ProductVariationDataStoreInterceptor::class,
        VariableProductDataStoreInterceptor::class,
        StockManagementSettingInterceptor::class,
        StoreLocationInterceptor::class,
        AddToCartInterceptor::class,
        MapInventoryJobInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getIntegrationName() : string
    {
        return self::NAME;
    }
}
