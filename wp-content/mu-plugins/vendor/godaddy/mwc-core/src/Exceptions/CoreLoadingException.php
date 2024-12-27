<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use Throwable;

/**
 * Sentry exception extension to handle failure to load MWC Core.
 */
class CoreLoadingException extends SentryException
{
    /**
     * Constructor.
     *
     * @param string $message exception message
     * @param Throwable|null $previous optional previous thrown exception
     * @throws Exception
     */
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);

        $this->code = 500;
        $this->level = 'error';
    }
}
