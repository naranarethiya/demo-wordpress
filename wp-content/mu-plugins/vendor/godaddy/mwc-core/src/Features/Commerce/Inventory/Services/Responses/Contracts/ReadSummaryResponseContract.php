<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;

/**
 * Contract to define default signatures for read {@see Summary} operation responses.
 */
interface ReadSummaryResponseContract
{
    /**
     * Gets the returned {@see Summary} by the read operation.
     *
     * @return Summary
     */
    public function getSummary() : Summary;
}
