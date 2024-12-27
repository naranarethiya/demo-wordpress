<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertSummaryInput;

interface CanUpdateSummariesContract
{
    /**
     * Updates the summary.
     *
     * @param UpsertSummaryInput $input
     *
     * @return Summary
     *
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function update(UpsertSummaryInput $input) : Summary;
}
