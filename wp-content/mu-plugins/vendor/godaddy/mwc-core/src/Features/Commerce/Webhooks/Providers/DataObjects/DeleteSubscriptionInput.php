<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

class DeleteSubscriptionInput extends AbstractDataObject
{
    /** @var string ID of the subscription to delete */
    public string $subscriptionId;

    /**
     * Delete subscription input constructor.
     *
     * @param array{
     *     subscriptionId: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
