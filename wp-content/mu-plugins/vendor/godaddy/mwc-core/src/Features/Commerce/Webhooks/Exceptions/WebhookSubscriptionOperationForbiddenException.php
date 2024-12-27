<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Exceptions thrown during webhook processing.
 */
class WebhookSubscriptionOperationForbiddenException extends SentryException
{
    use CanGetNewInstanceTrait;

    /** @var int exception code */
    protected $code = 403;

    /**
     * Constructor.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
