<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\AbstractDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers\VariableProductDataStoreHandler;

class VariableProductDataStoreInterceptor extends AbstractDataStoreInterceptor
{
    protected string $objectType = 'product-variable';
    protected string $handler = VariableProductDataStoreHandler::class;
}
