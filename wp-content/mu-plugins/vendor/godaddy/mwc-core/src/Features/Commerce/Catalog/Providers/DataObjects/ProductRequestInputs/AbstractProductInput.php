<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ChannelIds;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Base data object for interacting with a product.
 */
abstract class AbstractProductInput extends AbstractDataObject
{
    /** @var string */
    public string $storeId;

    /** @var ChannelIds */
    public ChannelIds $channelIds;

    /**
     * Constructor.
     *
     * @param array{
     *     storeId: string,
     *     channelIds?: ChannelIds
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
