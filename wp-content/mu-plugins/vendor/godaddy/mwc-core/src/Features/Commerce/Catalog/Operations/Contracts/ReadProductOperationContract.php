<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

/**
 * Contract for reading product operations.
 */
interface ReadProductOperationContract
{
    /**
     * Sets the local ID.
     *
     * @param int $value
     * @return ReadProductOperationContract
     */
    public function setLocalId(int $value) : ReadProductOperationContract;

    /**
     * Gets the local ID.
     *
     * @return int
     */
    public function getLocalId() : int;
}
