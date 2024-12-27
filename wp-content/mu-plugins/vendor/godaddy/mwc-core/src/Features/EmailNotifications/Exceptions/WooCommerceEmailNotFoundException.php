<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions;

use GoDaddy\WordPress\MWC\Common\Repositories\SentryRepository;
use GoDaddy\WordPress\MWC\Core\Exceptions\AbstractNotFoundException;
use function Sentry\captureException;

/**
 * An exception to be thrown when a WooCommerce email isn't found.
 */
class WooCommerceEmailNotFoundException extends AbstractNotFoundException
{
    /**
     * Deconstruct and capture the exception in Sentry.
     */
    public function __destruct()
    {
        // TODO: we need to find a better way for this exception to be part of the not found type as well as reporting to Sentry {nmolham 2021-10-13}
        if (SentryRepository::loadSDK()) {
            // consider moving this function to a `SentryRepository::captureException` method for proper mocking in tests
            captureException($this);
        }

        parent::__destruct();
    }
}
