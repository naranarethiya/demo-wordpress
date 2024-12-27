<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasLocalIdsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasRemoteIdsContract;

/**
 * Contract for operations that can list remote resources.
 */
interface ListRemoteResourcesOperationContract extends HasLocalIdsContract, HasRemoteIdsContract
{
}
