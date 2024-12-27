<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Product mapping service contract.
 *
 * Interface for a service that maps Products to Commerce products.
 */
interface ProductsMappingServiceContract extends MappingServiceContract
{
    /**
     * Saves the remote UUID and associates it with the given model.
     *
     * The service should determine the best way to save the remote UUID and store the association based on the properties of the model.
     *
     * @param Product|object $model
     * @param string $remoteId
     * @return void
     * @throws CommerceExceptionContract
     */
    public function saveRemoteId(object $model, string $remoteId) : void;

    /**
     * Gets the remote UUID associated with the given model.
     *
     * @param Product|object $model
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string;
}
