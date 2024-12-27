<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

/**
 * A contract for objects that have a URL.
 */
interface HasUrlContract
{
    /**
     * Gets the object's URL.
     *
     * @return string
     */
    public function getUrl() : string;

    /**
     * Sets the object's URL.
     *
     * @param string $value
     * @return $this
     */
    public function setUrl(string $value);
}
