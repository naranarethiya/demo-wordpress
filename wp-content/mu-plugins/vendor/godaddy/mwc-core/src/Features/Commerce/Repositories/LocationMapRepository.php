<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class LocationMapRepository extends AbstractResourceMapRepository
{
    /** @var string type of resources managed by this repository */
    protected string $resourceType = CommerceResourceTypes::InventoryLocation;
}
