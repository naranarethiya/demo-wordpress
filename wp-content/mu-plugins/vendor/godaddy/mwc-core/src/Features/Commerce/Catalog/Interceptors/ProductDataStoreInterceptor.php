<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ProductDataStoreHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\AbstractDataStoreInterceptor;

/**
 * Interceptor for overriding the registered data store for standard products.
 */
class ProductDataStoreInterceptor extends AbstractDataStoreInterceptor
{
    protected string $objectType = CatalogIntegration::PRODUCT_POST_TYPE;
    protected string $handler = ProductDataStoreHandler::class;

    /** @var int processing priority for the hook; we want this slightly lower than PHP_INT_MAX so that inventory service can take a higher priority */
    protected int $priority = PHP_INT_MAX - 1;
}
