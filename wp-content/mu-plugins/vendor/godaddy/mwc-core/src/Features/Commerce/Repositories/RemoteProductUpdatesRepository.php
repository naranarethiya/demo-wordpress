<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;

/**
 * Remote product updates repository.
 */
class RemoteProductUpdatesRepository extends AbstractResourceUpdatesRepository
{
    /** @var string type of resources managed by this repository */
    protected string $resourceType = CommerceResourceTypes::Product;
}
