<?php

namespace GoDaddy\WordPress\MWC\Core\Traits;

use GoDaddy\WordPress\MWC\Common\Traits\EnumTrait as CommonEnumTrait;

/**
 * Enables enum-like syntax pre PHP 8.1.
 *
 * @deprecated use {@see CommonEnumTrait} instead
 */
trait EnumTrait
{
    use CommonEnumTrait;
}
