<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

interface OrdersMappingServiceContract extends MappingServiceContract
{
    /**
     * {@inheritDoc}
     *
     * Saves the remote UUID and associates it with the given model. The service should determine the best way to save
     * the remote UUID and store the association based on the properties of the model.
     *
     * @param Order  $model
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
