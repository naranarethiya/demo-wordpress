<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

trait HasCommerceCapabilitiesTrait
{
    /**
     * Gets a list of capabilities.
     *
     * @return array<string, bool>
     */
    abstract public static function getCommerceCapabilities() : array;

    /**
     * Determines if the given capability is available.
     *
     * @param string $capability
     *
     * @return bool
     */
    public static function hasCommerceCapability(string $capability) : bool
    {
        return TypeHelper::bool(ArrayHelper::get(static::getCommerceCapabilities(), $capability, false), false);
    }
}
