<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

/**
 * Can Convert to Array Contract.
 */
interface CanConvertToArrayContract
{
    /**
     * Converts class data properties to an array.
     *
     * @return array
     */
    public function toArray() : array;
}
