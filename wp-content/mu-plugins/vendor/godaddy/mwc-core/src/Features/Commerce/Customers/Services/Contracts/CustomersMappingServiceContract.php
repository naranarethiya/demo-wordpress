<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;

interface CustomersMappingServiceContract extends MappingServiceContract
{
    /**
     * Saves the remote UUID and associates it with the given model. The service should determine the best way to save
     * the remote UUID and store the association based on the properties of the model.
     *
     * @param CustomerContract $model
     * @param string $remoteId
     *
     * @return void
     */
    public function saveRemoteId(object $model, string $remoteId) : void;

    /**
     * Gets the remote UUID associated with the given model.
     *
     * @param CustomerContract $model
     *
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string;
}
