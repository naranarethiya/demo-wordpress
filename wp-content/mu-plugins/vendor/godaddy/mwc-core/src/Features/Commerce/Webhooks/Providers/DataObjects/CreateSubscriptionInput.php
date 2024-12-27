<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Context;

/**
 * Input data object for creating a webhook subscription.
 */
class CreateSubscriptionInput extends AbstractDataObject
{
    /** @var Context The context (contains the storeId) */
    public Context $context;

    /** @var string the webhook callback URL */
    public string $deliveryUrl;

    /** @var string a description of the webhook subscription */
    public string $description;

    /** @var string[] the types of webhook events to subscribe to */
    public array $eventTypes;

    /** @var bool is the webhook enabled */
    public bool $isEnabled;

    /** @var string the webhook subscription name */
    public string $name;

    /**
     * Create Webhook Subscription Input Constructor.
     *
     * @param array{
     *     context: Context,
     *     deliveryUrl: string,
     *     description: string,
     *     eventTypes: string[],
     *     isEnabled: bool,
     *     name: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
