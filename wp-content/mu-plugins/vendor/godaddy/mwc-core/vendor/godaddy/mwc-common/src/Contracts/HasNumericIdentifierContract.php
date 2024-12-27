<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

/**
 * A contract for objects that can have a (possibly unique) numerical identifier.
 *
 * @see HasStringIdentifierContract for objects that have a string identifier instead
 */
interface HasNumericIdentifierContract
{
    /**
     * Gets the ID.
     *
     * @return int|null
     */
    public function getId() : ?int;

    /**
     * Sets the ID.
     *
     * @param int $value The value to set
     * @return $this
     */
    public function setId(int $value);
}
