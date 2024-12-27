<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Contracts\CanPersistMultiItemsRemoteIdsContract;

/**
 * @extends CanPersistMultiItemsRemoteIdsContract<LineItem>
 */
interface MultiLineItemsPersistentMappingServiceContract extends CanPersistMultiItemsRemoteIdsContract
{
}
