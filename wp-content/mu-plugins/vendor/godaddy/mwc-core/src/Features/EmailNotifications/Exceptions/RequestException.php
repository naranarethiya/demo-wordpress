<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use Throwable;

/**
 * An exception to be thrown if an error occurs in the response.
 */
class RequestException extends BaseException
{
    /**
     * Constructor.
     *
     * @param string $message
     * @param int|null $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, ?int $code = null, ?Throwable $previous = null)
    {
        if ($code) {
            $this->code = $code;
        }
        parent::__construct($message, $previous);
    }
}
