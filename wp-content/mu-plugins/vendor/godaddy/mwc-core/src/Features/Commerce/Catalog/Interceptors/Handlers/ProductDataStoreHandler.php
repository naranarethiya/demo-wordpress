<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\ProductsDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\Handlers\AbstractDataStoreHandler;

/**
 * Callback handler for {@see ProductDataStoreInterceptor}.
 */
class ProductDataStoreHandler extends AbstractDataStoreHandler
{
    public function __construct(ProductsDataStore $dataStore)
    {
        parent::__construct($dataStore);
    }
}
