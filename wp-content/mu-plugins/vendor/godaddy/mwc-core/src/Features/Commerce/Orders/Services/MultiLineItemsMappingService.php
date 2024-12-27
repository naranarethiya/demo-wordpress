<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItem as CommerceLineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\LineItemMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiLineItemsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash\CommerceLineItemHashService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash\LineItemHashService;

/**
 * @extends AbstractMultiItemsMappingService<LineItem, CommerceLineItem>
 */
class MultiLineItemsMappingService extends AbstractMultiItemsMappingService implements MultiLineItemsMappingServiceContract
{
    public function __construct(
        LineItemMappingServiceContract $mappingService,
        LineItemHashService $localModelHashService,
        CommerceLineItemHashService $commerceObjectHashService
    ) {
        parent::__construct($mappingService, $localModelHashService, $commerceObjectHashService);
    }
}
