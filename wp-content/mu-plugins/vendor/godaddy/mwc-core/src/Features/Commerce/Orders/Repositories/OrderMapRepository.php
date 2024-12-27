<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Repositories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class OrderMapRepository extends AbstractResourceMapRepository
{
    /** @var string type of resources managed by this repository */
    public string $resourceType = CommerceResourceTypes::Order;
}
