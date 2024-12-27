<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class ListSubscriptionsInput extends AbstractDataObject
{
    /** @var string the store ID */
    public string $storeId;

    /**
     * List Webhook Subscriptions Input Constructor.
     *
     * @param array{
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
