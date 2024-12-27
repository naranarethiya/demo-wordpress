<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasStringIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasUrlContract;

/**
 * A contract for handling image sizes.
 *
 * @see ImageContract
 */
interface ImageSizeContract extends HasStringIdentifierContract, HasUrlContract
{
    /**
     * Gets the object's height in pixels.
     *
     * @return int
     */
    public function getHeight() : int;

    /**
     * Sets the object's height in pixels.
     *
     * @param int $value
     * @return $this
     */
    public function setHeight(int $value);

    /**
     * Gets the object's width in pixels.
     *
     * @return int
     */
    public function getWidth() : int;

    /**
     * Sets the object's width in pixels.
     *
     * @param int $value
     * @return $this
     */
    public function setWidth(int $value);
}
