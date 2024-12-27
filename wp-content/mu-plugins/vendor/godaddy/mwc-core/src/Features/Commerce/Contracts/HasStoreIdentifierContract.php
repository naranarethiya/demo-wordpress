<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts;

interface HasStoreIdentifierContract
{
    /**
     * Gets store UUID.
     *
     * @return string
     */
    public function getStoreId() : string;

    /**
     * Sets store UUID.
     *
     * @param string $value
     * @return $this
     */
    public function setStoreId(string $value);
}
