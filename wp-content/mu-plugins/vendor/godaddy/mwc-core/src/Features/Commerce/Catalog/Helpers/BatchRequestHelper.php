<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Helper class for batched requests.
 */
class BatchRequestHelper
{
    /**
     * Gets the maximum number of items to include in a single API request when filtering by ID.
     *
     * @return positive-int
     */
    public static function getMaxIdsPerRequest() : int
    {
        return max(TypeHelper::int(Configuration::get('commerce.catalog.api.maxIdsPerRequest'), 50), 1);
    }
}
