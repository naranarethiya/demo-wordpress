<?php

namespace GoDaddy\WordPress\MWC\Common\Pipeline\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Pipeline\Pipeline;
use Throwable;

/**
 * Exception thrown when {@see Pipeline} is passed an invalid pipe.
 */
class InvalidPipeException extends BaseException
{
    public function __construct(string $message = 'Invalid Pipe', ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
