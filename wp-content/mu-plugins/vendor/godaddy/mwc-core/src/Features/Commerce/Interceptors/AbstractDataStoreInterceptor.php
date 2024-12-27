<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Register\Register;

class AbstractDataStoreInterceptor extends AbstractInterceptor
{
    /**
     * @var non-empty-string The data store type. Can be any of the keys in WooCommerce's WC_Data_Store::$store private property.
     */
    protected string $objectType;

    /**
     * @var class-string<AbstractInterceptorHandler>
     */
    protected string $handler;

    /** @var int processing priority for the hook */
    protected int $priority = PHP_INT_MAX;

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup($this->getFilterGroup())
            ->setHandler([$this->handler, 'handle'])
            ->setPriority($this->priority)
            ->execute();
    }

    /**
     * Uses the $objectType property to build the specific WC data store filter string.
     *
     * @return string
     */
    protected function getFilterGroup() : string
    {
        return 'woocommerce_'.$this->objectType.'_data_store';
    }
}
