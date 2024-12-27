<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

/**
 * A contract for objects that can have a string identifier.
 *
 * @see HasNumericIdentifierContract for identfiers that are integers instead
 */
interface HasStringIdentifierContract
{
    /**
     * Gets the ID.
     *
     * @return string
     */
    public function getId() : string;

    /**
     * Sets the ID.
     *
     * @param string $value The value to set
     * @return $this
     */
    public function setId(string $value);
}
