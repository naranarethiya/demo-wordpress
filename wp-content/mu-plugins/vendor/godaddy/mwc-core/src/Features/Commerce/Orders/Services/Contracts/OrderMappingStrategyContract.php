<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

interface OrderMappingStrategyContract extends MappingStrategyContract
{
    /**
     * {@inheritDoc}
     *
     * @param Order $model
     * @param string $remoteId
     *
     * @return void
     */
    public function saveRemoteId(object $model, string $remoteId) : void;

    /**
     * {@inheritDoc}
     *
     * @param Order $model
     *
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string;
}
