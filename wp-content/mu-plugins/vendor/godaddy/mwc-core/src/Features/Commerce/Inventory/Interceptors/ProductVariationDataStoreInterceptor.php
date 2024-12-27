<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\AbstractDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers\ProductVariationDataStoreHandler;

class ProductVariationDataStoreInterceptor extends AbstractDataStoreInterceptor
{
    protected string $objectType = 'product-variation';
    protected string $handler = ProductVariationDataStoreHandler::class;
}
