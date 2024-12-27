<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\AbstractBadRequestException as CommonAbstractBadRequestException;

/**
 * A base for bad request Exceptions.
 *
 * @deprecated use {@see CommonAbstractBadRequestException} instead.
 */
abstract class AbstractBadRequestException extends CommonAbstractBadRequestException
{
}
