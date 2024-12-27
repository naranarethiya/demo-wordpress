<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\WooCommerce\CustomerDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\Handlers\AbstractDataStoreHandler;

class CustomerDataStoreHandler extends AbstractDataStoreHandler
{
    public function __construct(CustomerDataStore $dataStore)
    {
        parent::__construct($dataStore);
    }
}
