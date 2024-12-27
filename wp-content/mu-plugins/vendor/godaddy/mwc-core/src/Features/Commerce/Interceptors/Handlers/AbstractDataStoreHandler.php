<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;

class AbstractDataStoreHandler extends AbstractInterceptorHandler
{
    protected object $dataStore;

    /**
     * @param object $dataStore
     */
    public function __construct(object $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        return $this->dataStore;
    }
}
