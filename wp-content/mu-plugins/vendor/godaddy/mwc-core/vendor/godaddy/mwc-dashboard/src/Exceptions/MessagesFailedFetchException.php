<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Sentry Exception Class that serves as a base to report to sentry.
 */
class MessagesFailedFetchException extends SentryException
{
    /** @var int exception code */
    protected $code = 400;

    /** @var string exception level */
    protected $level = 'error';

    /**
     * Exception constructor.
     *
     * @param string $message
     * @param int|null $code
     */
    public function __construct(string $message, int $code = null)
    {
        if ($code) {
            $this->code = $code;
        }

        parent::__construct($message);
    }
}
