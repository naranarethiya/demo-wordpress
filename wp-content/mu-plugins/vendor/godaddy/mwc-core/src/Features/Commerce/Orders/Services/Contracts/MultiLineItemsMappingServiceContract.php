<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Contracts\CanSaveMultiItemsRemoteIdsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItem as CommerceLineItem;

/**
 * @extends CanSaveMultiItemsRemoteIdsContract<LineItem, CommerceLineItem>
 */
interface MultiLineItemsMappingServiceContract extends CanSaveMultiItemsRemoteIdsContract
{
}
