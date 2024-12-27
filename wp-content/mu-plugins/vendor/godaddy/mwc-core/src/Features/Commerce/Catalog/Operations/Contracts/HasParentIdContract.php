<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

interface HasParentIdContract
{
    /**
     * Sets the parent ID.
     *
     * @param string|null $value
     * @return $this
     */
    public function setParentId(?string $value);

    /**
     * Gets the parent ID.
     *
     * @return string|null
     */
    public function getParentId() : ?string;
}
