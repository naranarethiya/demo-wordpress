<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\Handlers\AbstractDataStoreHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores\VariableProductDataStore;

class VariableProductDataStoreHandler extends AbstractDataStoreHandler
{
    public function __construct(VariableProductDataStore $dataStore)
    {
        parent::__construct($dataStore);
    }
}
