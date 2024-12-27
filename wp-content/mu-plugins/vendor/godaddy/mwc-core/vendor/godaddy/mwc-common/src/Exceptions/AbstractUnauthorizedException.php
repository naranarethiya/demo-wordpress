<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions;

/**
 * A base for unauthorized Exceptions.
 */
abstract class AbstractUnauthorizedException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 401;
}
