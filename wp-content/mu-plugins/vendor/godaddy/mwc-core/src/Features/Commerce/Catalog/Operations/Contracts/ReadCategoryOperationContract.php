<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

interface ReadCategoryOperationContract
{
    /**
     * Sets the local ID.
     *
     * @param int $value
     * @return ReadCategoryOperationContract
     */
    public function setLocalId(int $value) : ReadCategoryOperationContract;

    /**
     * Gets the local ID.
     *
     * @return int
     */
    public function getLocalId() : int;
}
