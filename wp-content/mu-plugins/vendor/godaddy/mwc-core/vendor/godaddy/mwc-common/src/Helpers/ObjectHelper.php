<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

use stdClass;

/**
 * A helper to manipulate objects.
 */
class ObjectHelper
{
    /**
     * Casts item as array if it is a valid object.
     *
     * @param mixed $item
     * @return array
     */
    public static function toArray($item) : array
    {
        return is_object($item) || $item instanceof stdClass ? (array) $item : $item;
    }
}
