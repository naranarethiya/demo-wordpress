<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions;

/**
 * A base for not found Exceptions.
 */
abstract class AbstractNotFoundException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 404;
}
