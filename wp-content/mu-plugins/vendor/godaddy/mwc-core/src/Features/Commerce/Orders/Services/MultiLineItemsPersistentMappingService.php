<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\LineItemMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiLineItemsPersistentMappingServiceContract;

/**
 * @extends AbstractMultiItemsPersistentMappingService<LineItem>
 */
class MultiLineItemsPersistentMappingService extends AbstractMultiItemsPersistentMappingService implements MultiLineItemsPersistentMappingServiceContract
{
    public function __construct(LineItemMappingServiceContract $mappingService)
    {
        parent::__construct($mappingService);
    }
}
