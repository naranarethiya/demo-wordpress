<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListSummariesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;

interface CanListSummariesContract
{
    /**
     * Lists summaries.
     *
     * @param ListSummariesInput $input
     *
     * @return Summary[]
     *
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function list(ListSummariesInput $input) : array;
}
