<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

/**
 * Represents a package type, e.g., flat rate padded envelope, large flat rate box, letter.
 */
interface PackageTypeContract extends ModelContract
{
    /**
     * Sets package code.
     *
     * @param string $value
     * @return $this
     */
    public function setCode(string $value);

    /**
     * Gets package code.
     *
     * @return string
     */
    public function getCode() : string;

    /**
     * Sets package name.
     *
     * @param string $value
     * @return $this
     */
    public function setName(string $value);

    /**
     * Gets package name.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Sets package description.
     *
     * @param string $value
     * @return $this
     */
    public function setDescription(string $value);

    /**
     * Gets package description.
     *
     * @return string
     */
    public function getDescription() : string;
}
