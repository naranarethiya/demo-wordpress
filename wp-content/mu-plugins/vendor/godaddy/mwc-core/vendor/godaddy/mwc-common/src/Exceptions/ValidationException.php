<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions;

/**
 * Exception to report a validation issue.
 */
class ValidationException extends SentryException
{
    /** @var int exception code */
    protected $code = 422;
}
