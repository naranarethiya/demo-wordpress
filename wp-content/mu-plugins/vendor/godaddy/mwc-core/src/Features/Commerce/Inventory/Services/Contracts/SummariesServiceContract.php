<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Exceptions\SummaryNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ListSummariesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadSummaryOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListSummariesResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadSummaryResponseContract;

interface SummariesServiceContract
{
    /**
     * Reads the inventory summary for a product.
     *
     * @param ReadSummaryOperationContract $operation
     *
     * @return ReadSummaryResponseContract
     * @throws MissingProductRemoteIdException|SummaryNotFoundException|CommerceExceptionContract|Exception
     */
    public function readSummary(ReadSummaryOperationContract $operation) : ReadSummaryResponseContract;

    /**
     * List inventory summaries.
     *
     * @param ListSummariesOperationContract $operation
     *
     * @return ListSummariesResponseContract
     *
     * @throws Exception
     */
    public function list(ListSummariesOperationContract $operation) : ListSummariesResponseContract;

    /**
     * Turns off the cache for this service.
     *
     * @return $this
     */
    public function withoutCache() : SummariesServiceContract;
}
