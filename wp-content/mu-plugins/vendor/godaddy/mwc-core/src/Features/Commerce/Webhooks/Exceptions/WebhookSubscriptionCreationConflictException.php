<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Exceptions thrown during webhook processing.
 */
class WebhookSubscriptionCreationConflictException extends BaseException
{
    use CanGetNewInstanceTrait;

    /** @var int exception code */
    protected $code = 409;

    /** @var string subscription id */
    protected string $subscriptionId = '';

    /**
     * Constructor.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->subscriptionId = StringHelper::substring(
            trim(
                StringHelper::after($message, 'existing subscription id is: ')
            ),
            0,
            36
        );

        parent::__construct($message);
    }

    /**
     * Get the subscription id.
     *
     * @return string
     */
    public function getSubscriptionId() : string
    {
        return $this->subscriptionId;
    }
}
