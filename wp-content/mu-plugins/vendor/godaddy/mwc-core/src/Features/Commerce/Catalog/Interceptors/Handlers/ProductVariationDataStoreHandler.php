<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\ProductVariationDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductVariationDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\Handlers\AbstractDataStoreHandler;

/**
 * Callback handler for {@see ProductVariationDataStoreInterceptor}.
 */
class ProductVariationDataStoreHandler extends AbstractDataStoreHandler
{
    /**
     * Constructor.
     *
     * @param ProductVariationDataStore $dataStore
     */
    public function __construct(ProductVariationDataStore $dataStore)
    {
        parent::__construct($dataStore);
    }
}
