<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

interface HasAltIdFilterContract
{
    /**
     * Set the alt ID.
     *
     * @param ?string $altId
     * @return $this
     */
    public function setAltId(?string $altId);

    /**
     * Get the alt ID.
     *
     * @return ?string
     */
    public function getAltId() : ?string;
}
