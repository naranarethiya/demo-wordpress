<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;

class Location extends AbstractDataObject
{
    public string $channelId;
    public string $alias;
    public ?Address $address = null;
    /** @var Contact[] */
    public array $contacts = [];

    /** @var string represents the RETAIL location type */
    const TYPE_RETAIL = 'RETAIL';

    /**
     * Creates a new data object.
     *
     * @param array{
     *     channelId: string,
     *     alias: string,
     *     address?: Address,
     *     contacts?: Contact[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
