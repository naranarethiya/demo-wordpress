<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\AbstractNotFoundException as CommonAbstractNotFoundException;

/**
 * A base for not found Exceptions.
 *
 * @deprecated use {@see CommonAbstractNotFoundException} instead.
 */
abstract class AbstractNotFoundException extends CommonAbstractNotFoundException
{
}
