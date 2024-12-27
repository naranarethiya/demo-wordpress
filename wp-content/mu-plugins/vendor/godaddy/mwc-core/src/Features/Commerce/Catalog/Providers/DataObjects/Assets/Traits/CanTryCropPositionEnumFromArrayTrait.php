<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

/**
 * Trait for trying a value from an array in a crop position enum-class.
 */
trait CanTryCropPositionEnumFromArrayTrait
{
    use EnumTrait;

    /**
     * Attempts to map a scalar via an array and key to an enum value or string.
     *
     * @param mixed $array
     * @param int|string $key
     * @param string $default
     * @return string
     */
    public static function tryFromArray($array, $key, string $default) : string
    {
        return static::tryFrom(TypeHelper::string(ArrayHelper::get($array, $key), '')) ?: $default;
    }
}
