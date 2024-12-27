<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\AbstractUnauthorizedException as CommonAbstractUnauthorizedException;

/**
 * A base for unauthorized Exceptions.
 *
 * @deprecated use {@see CommonAbstractUnauthorizedException} instead.
 */
abstract class AbstractUnauthorizedException extends CommonAbstractUnauthorizedException
{
}
