<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use Throwable;

/**
 * Sentry exception extension to handle failed sent events.
 */
class EventBridgeEventSendFailedException extends SentryException
{
    /**
     * EventBridgeEventSendFailedException constructor.
     *
     * @param string $message exception message
     * @param Throwable|null $previous optional previous exception thrown
     * @throws Exception
     */
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);

        $this->code = 500;
        $this->level = 'error';
    }
}
