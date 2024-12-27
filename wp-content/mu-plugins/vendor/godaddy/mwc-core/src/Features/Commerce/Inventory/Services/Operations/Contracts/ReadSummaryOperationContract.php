<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;

/**
 * Contract to define default signatures for read {@see Summary} operations.
 */
interface ReadSummaryOperationContract
{
    /**
     * Gets the product ID that will have the summary reading in this operation.
     *
     * @return int
     */
    public function getProductId() : int;
}
