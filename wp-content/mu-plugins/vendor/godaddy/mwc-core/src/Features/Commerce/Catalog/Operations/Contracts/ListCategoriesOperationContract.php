<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;

/**
 * List categories operation contract.
 */
interface ListCategoriesOperationContract extends
    CanConvertToArrayContract,
    HasAltIdFilterContract,
    HasPageSizeContract,
    HasPageTokenContract,
    HasParentIdContract,
    HasSortingContract,
    ListRemoteResourcesOperationContract
{
}
