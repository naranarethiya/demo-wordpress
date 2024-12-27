<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * An exception to be thrown for an invalid application id.
 */
class InvalidApplicationIdException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 409;
}
