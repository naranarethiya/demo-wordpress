<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\VariableProductDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\VariableProductDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\Handlers\AbstractDataStoreHandler;

/**
 * Callback handler for {@see VariableProductDataStoreInterceptor}.
 */
class VariableProductDataStoreHandler extends AbstractDataStoreHandler
{
    /**
     * Constructor.
     *
     * @param VariableProductDataStore $dataStore
     */
    public function __construct(VariableProductDataStore $dataStore)
    {
        parent::__construct($dataStore);
    }
}
