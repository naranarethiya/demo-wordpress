<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Contract for services that insert a local entity in the database based on the provided remote resource.
 */
interface InsertLocalResourceServiceContract
{
    /**
     * Inserts a record into the local database to correspond to the supplied remote resource.
     *
     * @param AbstractDataObject $remoteResource
     * @return int
     */
    public function insert(AbstractDataObject $remoteResource) : int;
}
