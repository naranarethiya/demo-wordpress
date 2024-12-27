<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions;

use GoDaddy\WordPress\MWC\Common\Repositories\SentryRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use Throwable;

/**
 * Sentry Exception Class that serves as a base to report to sentry.
 *
 * @method static static getNewInstance(string $message, ?Throwable $previous = null)
 */
class SentryException extends BaseException
{
    use CanGetNewInstanceTrait;

    /**
     * Deconstruct.
     */
    public function __destruct()
    {
        if (SentryRepository::loadSDK()) {
            \Sentry\captureException($this);
        }

        parent::__destruct();
    }
}
