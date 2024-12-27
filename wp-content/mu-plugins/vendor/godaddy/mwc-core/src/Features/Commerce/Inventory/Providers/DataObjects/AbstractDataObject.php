<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use DateTime;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject as CommerceAbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;

abstract class AbstractDataObject extends CommerceAbstractDataObject
{
    /** @var ExternalId[] */
    public array $externalIds = [];

    public ?DateTime $createdAt = null;
    public ?DateTime $updatedAt = null;

    /**
     * Creates a new data object.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
