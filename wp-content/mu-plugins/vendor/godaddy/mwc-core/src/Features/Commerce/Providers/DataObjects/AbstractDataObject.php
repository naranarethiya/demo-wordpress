<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject as CommonAbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Contracts\DataObjectContract;

/**
 * An abstract data object.
 *
 * @deprecated use {@see CommonAbstractDataObject} instead.
 *
 * @method static static getNewInstance(array $data)
 */
abstract class AbstractDataObject extends CommonAbstractDataObject implements DataObjectContract
{
}
