<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractTemporaryMappingStrategy;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class TemporaryOrderMappingStrategy extends AbstractTemporaryMappingStrategy implements OrderMappingStrategyContract
{
    /**
     * {@inheritDoc}
     *
     * @param Order $model
     */
    protected function getTemporaryKey(object $model) : ?string
    {
        return $model->getCartId();
    }
}
