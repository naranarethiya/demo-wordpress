<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;

interface ListSummariesResponseContract
{
    /**
     * Gets the summaries.
     *
     * @return Summary[]
     */
    public function getSummaries() : array;
}
