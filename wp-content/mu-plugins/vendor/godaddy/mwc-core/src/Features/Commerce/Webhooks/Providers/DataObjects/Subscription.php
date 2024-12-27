<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Context;

/**
 * A webhook subscription.
 */
class Subscription extends AbstractDataObject
{
    /** @var string the subscription ID */
    public string $id;

    /** @var string the subscription name */
    public string $name;

    /** @var string|null the subscription description */
    public ?string $description = null;

    /** @var Context */
    public Context $context;

    /** @var string[] the subscription event types */
    public array $eventTypes;

    /** @var string the subscription delivery URL */
    public string $deliveryUrl;

    /** @var bool is the subscription enabled */
    public bool $isEnabled;

    /** @var string|null the subscription creation time */
    public ?string $createdAt;

    /** @var string|null the subscription update time */
    public ?string $updatedAt;

    /** @var string|null the subscription secret */
    public ?string $secret = null;

    /**
     * Subscription constructor.
     *
     * @param array{
     *     id: string,
     *     name: string,
     *     description?: string|null,
     *     context: Context,
     *     eventTypes: string[],
     *     deliveryUrl: string,
     *     isEnabled: bool,
     *     createdAt: string|null,
     *     updatedAt: string|null,
     *     secret?: string|null
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
