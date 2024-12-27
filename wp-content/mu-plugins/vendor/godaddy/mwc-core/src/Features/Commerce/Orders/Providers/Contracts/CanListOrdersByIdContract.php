<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\ListOrdersByIdInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrdersListOutput;

interface CanListOrdersByIdContract
{
    /**
     * Gets a list of orders by their IDs.
     *
     * @param ListOrdersByIdInput $input
     * @return OrdersListOutput
     * @throws CommerceExceptionContract
     */
    public function listById(ListOrdersByIdInput $input) : OrdersListOutput;
}
