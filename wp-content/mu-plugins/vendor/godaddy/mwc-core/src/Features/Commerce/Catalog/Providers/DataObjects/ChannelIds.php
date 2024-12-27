<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * DTO that holds channel IDs data.
 *
 * This is used by product operations when adding or removing a product to or from Catalog.
 *
 * @method static static getNewInstance(array $data)
 */
class ChannelIds extends AbstractDataObject
{
    /** @var string[] Channels to add */
    public array $add = [];

    /** @var string[] Channels to remove */
    public array $remove = [];

    /**
     * Creates a new data object.
     *
     * @param array{
     *     add?: string[],
     *     remove?: string[]
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
