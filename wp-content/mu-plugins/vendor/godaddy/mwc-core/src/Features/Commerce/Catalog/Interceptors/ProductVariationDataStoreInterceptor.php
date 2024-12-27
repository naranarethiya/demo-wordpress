<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ProductVariationDataStoreHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\AbstractDataStoreInterceptor;

/**
 * Interceptor for overriding the registered data store for product variations.
 */
class ProductVariationDataStoreInterceptor extends AbstractDataStoreInterceptor
{
    protected string $objectType = 'product-variation';
    protected string $handler = ProductVariationDataStoreHandler::class;

    /** @var int processing priority for the hook; we want this slightly lower than PHP_INT_MAX so that inventory service can take a higher priority */
    protected int $priority = PHP_INT_MAX - 1;
}
