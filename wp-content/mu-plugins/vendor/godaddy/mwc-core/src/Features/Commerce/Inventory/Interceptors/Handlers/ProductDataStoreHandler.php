<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\Handlers\AbstractDataStoreHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores\ProductDataStore;

class ProductDataStoreHandler extends AbstractDataStoreHandler
{
    public function __construct(ProductDataStore $dataStore)
    {
        parent::__construct($dataStore);
    }
}
