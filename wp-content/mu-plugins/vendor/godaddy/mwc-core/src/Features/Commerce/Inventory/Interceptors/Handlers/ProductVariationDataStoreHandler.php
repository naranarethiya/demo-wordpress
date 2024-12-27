<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\Handlers\AbstractDataStoreHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores\ProductVariationDataStore;

class ProductVariationDataStoreHandler extends AbstractDataStoreHandler
{
    public function __construct(ProductVariationDataStore $dataStore)
    {
        parent::__construct($dataStore);
    }
}
