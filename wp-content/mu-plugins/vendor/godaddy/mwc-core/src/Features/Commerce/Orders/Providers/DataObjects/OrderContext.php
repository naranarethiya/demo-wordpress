<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class OrderContext extends AbstractDataObject
{
    /** @var string Unique ID of the channel */
    public string $channelId;

    /** @var string|null identifier for the service or integration that created the order */
    public ?string $owner = null;

    /** @var string Unique ID of the store */
    public string $storeId;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     channelId: string,
     *     owner?: ?string,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
